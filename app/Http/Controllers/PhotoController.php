<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;

class PhotoController extends Controller
{
    /**
     * Devuelve la lista paginada de productos con estado de foto.
     * Query params:
     *   - search       : texto libre (busca en name, sku/code)
     *   - photo_status : 'pending' | 'done' | 'all'  (default: 'all')
     *   - per_page     : int (default: 20)
     *   - page         : int (default: 1)
     */
    public function getProducts(Request $request)
    {
        $query = Product::orderBy('producto');

        // Búsqueda por nombre o código (columnas reales de rodcas)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('producto', 'like', "%{$s}%")
                  ->orWhere('codigo',   'like', "%{$s}%");
            });
        }

        // Filtro por estado de foto
        $status = $request->get('photo_status', 'all');
        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('image')
                  ->orWhere('photo_verified', 0);
            });
        } elseif ($status === 'done') {
            $query->where('photo_verified', 1);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        // Estadísticas globales (independiente del filtro, columnas rodcas)
        $stats = [
            'total'    => Product::count(),
            'verified' => Product::where('photo_verified', 1)->count(),
            'pending'  => Product::where(function ($q) {
                                $q->whereNull('image')->orWhere('photo_verified', 0);
                            })->count(),
        ];

        // Mapear campos al frontend (columnas reales de rodcas)
        $items = $paginated->getCollection()->map(fn($p) => [
            'id'             => $p->id,
            'sku'            => $p->codigo,
            'name'           => trim($p->producto),
            'brand'          => null,
            'category'       => $p->dpto ?? 'Sin categoría',
            'sale_price'     => $p->p_venta,
            'image'          => $p->image,
            'photo_verified' => (bool) ($p->photo_verified ?? false),
        ]);

        $paginated->setCollection($items);

        return response()->json(array_merge($paginated->toArray(), ['stats' => $stats]));
    }

    /**
     * Guarda la foto de un producto.
     * Acepta dos modos:
     *   1. JSON  { source: 'url',    url: 'https://...' }
     *   2. Form  { source: 'upload', photo: <file> }
     *
     * Devuelve { success, image_url }
     */
    public function savePhoto(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $source  = $request->input('source', 'url');

        if ($source === 'url') {
            $request->validate(['url' => 'required|url|max:500']);
            $imageUrl = $request->url;

            // Descargar la imagen y guardarla localmente para no depender
            // de que la URL externa siempre esté disponible.
            try {
                $contents = $this->downloadImageFromUrl($imageUrl);
                $imageUrl = $this->storeImageContents($contents, $product->code ?? $id);
            } catch (\Exception $e) {
                // Si no se puede descargar, guarda solo la URL externa.
                // Esto permite usar URLs de proveedores sin descargar.
                \Log::warning("No se pudo descargar imagen para producto {$id}: " . $e->getMessage());
                // $imageUrl ya tiene la URL original, se guarda tal cual.
            }
        } elseif ($source === 'upload') {
            $request->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            ]);
            $imageUrl = $this->storeUploadedFile($request->file('photo'), $product->code ?? $id);
        } else {
            return response()->json(['success' => false, 'message' => 'Fuente no válida'], 422);
        }

        // Eliminar foto anterior si era un archivo local
        $this->deleteOldLocalPhoto($product->image);

        $product->update([
            'image'          => $imageUrl,
            'photo_verified' => true,
        ]);

        return response()->json([
            'success'   => true,
            'image_url' => $imageUrl,
            'message'   => 'Foto guardada correctamente',
        ]);
    }

    /**
     * Elimina la foto de un producto.
     */
    public function deletePhoto($id)
    {
        $product = Product::findOrFail($id);

        $this->deleteOldLocalPhoto($product->image);

        $product->update([
            'image'          => null,
            'photo_verified' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Foto eliminada']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Descarga el contenido binario de una URL de imagen.
     * Lanza excepción si falla o si la respuesta no es una imagen.
     */
    private function downloadImageFromUrl(string $url): string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout'        => 10,
                'follow_location' => true,
                'user_agent'     => 'Mozilla/5.0 LuraPos/1.0',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $contents = @file_get_contents($url, false, $ctx);

        if ($contents === false || strlen($contents) < 100) {
            throw new \RuntimeException("No se pudo descargar la imagen desde la URL");
        }

        // Verificación básica: los primeros bytes deben coincidir con firmas de imagen
        $magic = substr($contents, 0, 4);
        $isImage = (
            str_starts_with($contents, "\xFF\xD8")         // JPEG
            || str_starts_with($contents, "\x89PNG")       // PNG
            || str_starts_with($contents, "RIFF")          // WEBP (contiene RIFF)
            || str_starts_with($contents, "GIF8")          // GIF
        );

        if (!$isImage) {
            throw new \RuntimeException("El contenido descargado no es una imagen válida");
        }

        return $contents;
    }

    /**
     * Guarda el contenido binario de una imagen en storage/app/public/productos/fotos/
     * y devuelve la URL pública.
     */
    private function storeImageContents(string $contents, string $productCode): string
    {
        // Detectar extensión por los bytes de cabecera
        if (str_starts_with($contents, "\xFF\xD8")) {
            $ext = 'jpg';
        } elseif (str_starts_with($contents, "\x89PNG")) {
            $ext = 'png';
        } elseif (str_starts_with($contents, "GIF8")) {
            $ext = 'gif';
        } else {
            $ext = 'webp';
        }

        $slug     = Str::slug($productCode);
        $filename = "productos/fotos/{$slug}_" . time() . ".{$ext}";

        Storage::disk('public')->put($filename, $contents);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Guarda un UploadedFile en storage y devuelve la URL pública.
     */
    private function storeUploadedFile($file, string $productCode): string
    {
        $slug = Str::slug($productCode);
        $ext  = $file->getClientOriginalExtension() ?: $file->extension();
        $name = "productos/fotos/{$slug}_" . time() . ".{$ext}";

        Storage::disk('public')->put($name, file_get_contents($file->getRealPath()));

        return Storage::disk('public')->url($name);
    }

    /**
     * Elimina el archivo local si la URL apunta a nuestro storage.
     */
    private function deleteOldLocalPhoto(?string $imageUrl): void
    {
        if (!$imageUrl) return;

        // Solo eliminar si es un archivo local (contiene /storage/)
        if (!str_contains($imageUrl, '/storage/')) return;

        // Extraer la ruta relativa: quitar dominio y '/storage/'
        $path = preg_replace('#^.*/storage/#', '', $imageUrl);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
