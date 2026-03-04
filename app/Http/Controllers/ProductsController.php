<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\CashMovement;
use App\Models\Credit;
use App\Models\InventoryMovement;
use App\Services\SaleService;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

/**
 * Controlador adaptado a la BD rodcas.
 *
 * Tabla de productos: 'productos'
 * Columnas: id, codigo, producto, p_costo, p_venta, p_mayoreo,
 *           existencia, inv_min, inv_max, dpto, image, photo_verified
 *
 * 'dpto' actúa como categoría (texto libre, sin tabla categories).
 */
class ProductsController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    // ─────────────────────────────────────────────────────────────────────
    // VISTAS
    // ─────────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('welcome');
    }

    public function inventoryView()
    {
        // Departamentos únicos como "categorías"
        $categories = DB::table('productos')
            ->whereNotNull('dpto')
            ->where('dpto', '!=', '')
            ->distinct()
            ->orderBy('dpto')
            ->pluck('dpto');

        $products = Product::orderBy('producto')->get();

        return view('inventory', compact('categories', 'products'));
    }

    public function fotosView()
    {
        return view('fotos');
    }

    // ─────────────────────────────────────────────────────────────────────
    // API POS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /api/pos/products
     * Parámetros: search, category
     */
    public function getProducts(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('producto', 'like', "%{$s}%")
                  ->orWhere('codigo',   'like', "%{$s}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'Todas') {
            $query->where('dpto', $request->category);
        }

        $products = $query->get()->map(fn($p) => [
            'id'       => $p->id,
            'code'     => trim($p->codigo),
            'name'     => trim($p->producto),
            'category' => $p->dpto ?? 'Sin categoría',
            'size'     => null,
            'stock'    => (int) $p->existencia,
            'price'    => (float) $p->p_venta,
            'wholesale'=> (float) $p->p_mayoreo,
            'cost'     => (float) $p->p_costo,
            'image'    => $p->image,
        ]);

        return response()->json($products);
    }

    /**
     * GET /api/pos/categories
     * Devuelve los departamentos únicos como si fueran categorías.
     */
    public function getCategories()
    {
        $dptos = DB::table('productos')
            ->whereNotNull('dpto')
            ->where('dpto', '!=', '')
            ->where('dpto', '!=', 'N/A')
            ->distinct()
            ->orderBy('dpto')
            ->pluck('dpto');

        return response()->json(['Todas', ...$dptos]);
    }

    /**
     * GET /api/pos/branches
     */
    public function getBranches()
    {
        // rodcas no tiene tabla branches todavía; devolvemos una sucursal por defecto
        return response()->json([
            ['id' => 1, 'name' => 'Sucursal Principal', 'code' => 'MAIN'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // VENTAS
    // ─────────────────────────────────────────────────────────────────────

    public function processSale(Request $request)
    {
        $validated = $request->validate([
            'customer_name'        => 'nullable|string|max:255',
            'customer_email'       => 'nullable|email',
            'payment_method'       => 'required|in:efectivo,tarjeta,transferencia,mixto,credito',
            'amount_paid'          => 'required|numeric|min:0',
            'change_amount'        => 'nullable|numeric|min:0',
            'subtotal'             => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'total'                => 'required|numeric|min:0',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:productos,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.total'        => 'required|numeric|min:0',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'branch_id'            => 'nullable|integer',
        ]);

        try {
            $sale = $this->saleService->processSale([
                'customer_name'  => $validated['customer_name'] ?? 'Cliente',
                'customer_id'    => $validated['customer_id'] ?? null,
                'subtotal'       => $validated['subtotal'],
                'discount'       => $validated['discount'] ?? 0,
                'total'          => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'amount_paid'    => $validated['amount_paid'],
                'change_amount'  => $validated['change_amount'] ?? 0,
                'items'          => $validated['items'],
                'branch_id'      => $validated['branch_id'] ?? null,
            ]);

            $emailSent = false;

            if (!empty($validated['customer_email'])) {
                try {
                    $customer = \App\Models\Customer::firstOrCreate(
                        ['email' => $validated['customer_email']],
                        ['name'  => $validated['customer_name'] ?? 'Cliente']
                    );
                    $sale->update(['customer_id' => $customer->id]);
                    $sale    = $sale->load('details');
                    $pdf     = Pdf::loadView('emails.pdf-comprobant', compact('sale'));
                    $pdfData = $pdf->output();

                    \Mail::to($validated['customer_email'])
                         ->send(new \App\Mail\SaleReceipt($sale, $pdfData));
                    $emailSent = true;
                    $sale->update(['email_sent' => true]);
                } catch (\Exception $e) {
                    \Log::error("Error enviando comprobante: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'data'    => [
                    'invoice_number' => $sale->invoice_number,
                    'customer_name'  => $sale->customer->name ?? 'Cliente',
                    'total'          => $sale->total,
                    'change'         => $sale->change_amount,
                    'email_sent'     => $emailSent,
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al procesar venta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar venta: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function getSales(Request $request)
    {
        $query = Sale::with(['user', 'customer'])->orderBy('sale_date', 'desc');

        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function getSale($id)
    {
        $sale = Sale::with(['user', 'customer', 'details'])->findOrFail($id);
        return response()->json($sale);
    }

    public function cancelSale($id)
    {
        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status === 'cancelada') {
                return response()->json(['success' => false, 'message' => 'La venta ya está cancelada'], 422);
            }

            $this->saleService->cancelSale($sale);

            return response()->json(['success' => true, 'message' => 'Venta cancelada exitosamente']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al cancelar venta: ' . $e->getMessage()], 422);
        }
    }

    public function resendReceipt($id, Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        try {
            $sale     = Sale::findOrFail($id);
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => $validated['email']],
                ['name'  => 'Cliente']
            );
            $sale->update(['customer_id' => $customer->id]);
            $sent = $this->saleService->sendReceipt($sale);

            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'Comprobante enviado exitosamente' : 'Error al enviar el comprobante',
            ], $sent ? 200 : 422);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDashboardData($request);
        }
        return view('dashboard');
    }

    public function getDashboardData(Request $request)
    {
        $branchId  = $request->get('branch_id');
        $startDate = Carbon::parse($request->get('date_from', today()))->startOfDay();
        $endDate   = Carbon::parse($request->get('date_to',   today()))->endOfDay();

        $salesQuery = Sale::where('status', 'completada')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $stats = [
            'total_sales'        => (clone $salesQuery)->sum('total'),
            'transactions_count' => (clone $salesQuery)->count(),
            'average_ticket'     => (clone $salesQuery)->avg('total'),

            // Stock bajo usando columnas reales de rodcas
            'low_stock_products' => DB::table('productos')
                ->whereRaw('existencia <= inv_min')
                ->whereNull('deleted_at')
                ->count(),

            'daily_sales' => Sale::where('status', 'completada')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->selectRaw('DATE(sale_date) as date, SUM(total) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // Top productos usando la tabla real 'productos'
            'top_products' => DB::table('sale_details')
                ->join('productos', 'sale_details.product_id', '=', 'productos.id')
                ->join('sales',     'sale_details.sale_id',    '=', 'sales.id')
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->where('sales.status', 'completada')
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->selectRaw('productos.producto as name, SUM(sale_details.quantity) as total_sold, SUM(sale_details.total) as revenue')
                ->groupBy('productos.id', 'productos.producto')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get(),

            'sales_by_method' => (clone $salesQuery)
                ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
                ->groupBy('payment_method')
                ->get(),

            'sales_detail' => DB::table('sales')
                ->join('sale_details', 'sales.id',           '=', 'sale_details.sale_id')
                ->join('productos',    'sale_details.product_id', '=', 'productos.id')
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->where('sales.status', 'completada')
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->select(
                    'sales.id',
                    'sales.sale_date',
                    'sales.payment_method',
                    DB::raw('SUM(sale_details.quantity) as total_quantity'),
                    DB::raw('SUM(sale_details.total) as total_amount'),
                    DB::raw('GROUP_CONCAT(CONCAT(productos.producto, " (x", sale_details.quantity, ")") SEPARATOR ", ") as products')
                )
                ->groupBy('sales.id', 'sales.sale_date', 'sales.payment_method')
                ->orderBy('sales.sale_date', 'desc')
                ->get(),
        ];

        return response()->json($stats);
    }

    // ─────────────────────────────────────────────────────────────────────
    // INVENTARIO API (JSON – sin recarga de página)
    // ─────────────────────────────────────────────────────────────────────

    /** GET /api/pos/products/{id} */
    public function getProduct($id)
    {
        $p = Product::findOrFail($id);
        return response()->json($this->productToArray($p));
    }

    /** POST /api/pos/products  – crear producto */
    public function storeProduct(Request $request)
    {
        $v = $request->validate([
            'codigo'     => 'required|string|max:255|unique:productos,codigo',
            'producto'   => 'required|string|max:255',
            'dpto'       => 'nullable|string|max:255',
            'existencia' => 'nullable|integer|min:0',
            'inv_min'    => 'nullable|integer|min:0',
            'inv_max'    => 'nullable|integer|min:0',
            'p_costo'    => 'required|numeric|min:0',
            'p_venta'    => 'required|numeric|min:0',
            'p_mayoreo'  => 'nullable|numeric|min:0',
        ]);

        $product = Product::create([
            'codigo'     => $v['codigo'],
            'producto'   => $v['producto'],
            'dpto'       => $v['dpto']       ?? null,
            'existencia' => $v['existencia'] ?? 0,
            'inv_min'    => $v['inv_min']    ?? 1,
            'inv_max'    => $v['inv_max']    ?? 10,
            'p_costo'    => $v['p_costo'],
            'p_venta'    => $v['p_venta'],
            'p_mayoreo'  => $v['p_mayoreo']  ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente',
            'data'    => $this->productToArray($product),
        ], 201);
    }

    /** PUT /api/pos/products/{id} – actualizar producto */
    public function updateProduct($id, Request $request)
    {
        $product = Product::findOrFail($id);

        $v = $request->validate([
            'codigo'     => "required|string|max:255|unique:productos,codigo,{$product->id}",
            'producto'   => 'required|string|max:255',
            'dpto'       => 'nullable|string|max:255',
            'existencia' => 'nullable|integer|min:0',
            'inv_min'    => 'nullable|integer|min:0',
            'inv_max'    => 'nullable|integer|min:0',
            'p_costo'    => 'required|numeric|min:0',
            'p_venta'    => 'required|numeric|min:0',
            'p_mayoreo'  => 'nullable|numeric|min:0',
        ]);

        $product->update($v);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado',
            'data'    => $this->productToArray($product->fresh()),
        ]);
    }

    /** DELETE /api/pos/products/{id} */
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['success' => true, 'message' => 'Producto eliminado']);
    }

    /** POST /api/pos/products/{id}/entrada – entrada de mercancía */
    public function stockEntry($id, Request $request)
    {
        $v = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes'    => 'nullable|string|max:255',
            'p_costo'  => 'nullable|numeric|min:0',
            'p_venta'  => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($id);

        DB::beginTransaction();
        try {
            $stockBefore = (int) $product->existencia;
            $product->increment('existencia', $v['quantity']);

            $updates = [];
            if (isset($v['p_costo']) && $v['p_costo'] > 0) $updates['p_costo'] = $v['p_costo'];
            if (isset($v['p_venta']) && $v['p_venta'] > 0) $updates['p_venta'] = $v['p_venta'];
            if ($updates) $product->update($updates);

            $product->refresh();

            InventoryMovement::create([
                'product_id'    => $product->id,
                'user_id'       => auth()->id() ?? 1,
                'type'          => 'entrada',
                'quantity'      => $v['quantity'],
                'stock_before'  => $stockBefore,
                'stock_after'   => (int) $product->existencia,
                'reference'     => 'ENT-' . date('YmdHis'),
                'notes'         => $v['notes'] ?? 'Entrada de mercancía',
                'movement_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => "+{$v['quantity']} unidades registradas",
                'stock_before' => $stockBefore,
                'stock_after'  => (int) $product->existencia,
                'data'         => $this->productToArray($product),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** GET /api/pos/inventory-movements – movimientos (entradas/salidas de stock) */
    public function getInventoryMovements(Request $request)
    {
        $query = InventoryMovement::with('product:id,producto,codigo')
            ->orderBy('movement_date', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('movement_date', $request->date);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json(
            $query->limit(50)->get()->map(fn($m) => [
                'id'           => $m->id,
                'product_id'   => $m->product_id,
                'product_name' => trim($m->product->producto ?? '—'),
                'product_code' => trim($m->product->codigo   ?? ''),
                'type'         => $m->type,
                'quantity'     => $m->quantity,
                'stock_before' => $m->stock_before,
                'stock_after'  => $m->stock_after,
                'reference'    => $m->reference,
                'notes'        => $m->notes,
                'movement_date'=> $m->movement_date,
            ])
        );
    }

    /** Helper: Product → array JSON uniforme */
    private function productToArray(Product $p): array
    {
        return [
            'id'        => $p->id,
            'code'      => trim($p->codigo),
            'name'      => trim($p->producto),
            'category'  => $p->dpto ?? '',
            'stock'     => (int) $p->existencia,
            'inv_min'   => (int) $p->inv_min,
            'inv_max'   => (int) $p->inv_max,
            'price'     => (float) $p->p_venta,
            'wholesale' => (float) $p->p_mayoreo,
            'cost'      => (float) $p->p_costo,
            'image'     => $p->image,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // CRUD INVENTARIO legacy (form POST → redirect, para compatibilidad)
    // ─────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo'     => 'required|string|max:255|unique:productos,codigo',
            'producto'   => 'required|string|max:255',
            'dpto'       => 'nullable|string|max:255',
            'existencia' => 'required|integer|min:0',
            'inv_min'    => 'required|integer|min:0',
            'inv_max'    => 'nullable|integer|min:0',
            'p_costo'    => 'required|numeric|min:0',
            'p_venta'    => 'required|numeric|min:0',
            'p_mayoreo'  => 'nullable|numeric|min:0',
        ]);

        Product::create($validated);

        return redirect()->route('inventory.view')->with('success', 'Producto agregado correctamente.');
    }

    public function edit(Product $product)
    {
        $categories = DB::table('productos')
            ->whereNotNull('dpto')->where('dpto', '!=', '')
            ->distinct()->orderBy('dpto')->pluck('dpto');
        $products = Product::orderBy('producto')->get();
        return view('inventory', compact('products', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'codigo'  => "required|unique:productos,codigo,{$product->id}",
            'producto'=> 'required|string',
            'p_costo' => 'required|numeric|min:0',
            'p_venta' => 'required|numeric|min:0',
        ]);

        $product->update($request->only([
            'codigo', 'producto', 'dpto',
            'existencia', 'inv_min', 'inv_max',
            'p_costo', 'p_venta', 'p_mayoreo',
        ]));

        return redirect()->route('inventory.view')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('inventory.view')->with('success', 'Producto eliminado.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // CLIENTES
    // ─────────────────────────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $search = trim($request->get('search', ''));

        $query = Customer::query()->orderBy('name')->limit(10);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get(['id', 'name', 'phone']));
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $customer->only(['id', 'name', 'phone']),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────
    // MOVIMIENTOS DE EFECTIVO
    // ─────────────────────────────────────────────────────────────────────

    public function storeCashMovement(Request $request)
    {
        $validated = $request->validate([
            'type'      => 'nullable|in:entrada,salida',
            'concept'   => 'required|string|max:255',
            'amount'    => 'required|numeric|min:0.01',
            'notes'     => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer',
        ]);

        $validated['type'] = $validated['type'] ?? 'salida';

        $movement = CashMovement::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $movement,
        ], 201);
    }

    public function getCashMovements(Request $request)
    {
        $query = CashMovement::orderBy('created_at', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json($query->limit(100)->get());
    }

    // ─────────────────────────────────────────────────────────────────────
    // CRÉDITOS (cuentas por cobrar)
    // ─────────────────────────────────────────────────────────────────────

    public function getCredits(Request $request)
    {
        $query = Credit::with('sale:id,invoice_number,sale_date')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pendiente');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhereHas('sale', fn($sq) => $sq->where('invoice_number', 'like', "%{$s}%"));
            });
        }

        return response()->json($query->limit(100)->get()->map(fn($c) => [
            'id'              => $c->id,
            'sale_id'         => $c->sale_id,
            'invoice_number'  => $c->sale->invoice_number ?? '-',
            'sale_date'       => $c->sale?->sale_date,
            'customer_id'     => $c->customer_id,
            'customer_name'   => $c->customer_name,
            'original_amount' => (float) $c->original_amount,
            'paid_amount'     => (float) $c->paid_amount,
            'remaining'       => $c->remaining,
            'status'          => $c->status,
            'created_at'      => $c->created_at,
        ]));
    }

    public function payCredit($id, Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes'  => 'nullable|string|max:255',
        ]);

        $credit = Credit::findOrFail($id);

        if ($credit->status === 'pagado') {
            return response()->json(['success' => false, 'message' => 'Este crédito ya está liquidado'], 422);
        }

        $newPaid = (float) $credit->paid_amount + (float) $validated['amount'];

        if ($newPaid > (float) $credit->original_amount) {
            return response()->json([
                'success' => false,
                'message' => 'El abono supera el saldo restante ($' . number_format($credit->remaining, 2) . ')',
            ], 422);
        }

        $status = $newPaid >= (float) $credit->original_amount ? 'pagado' : 'pendiente';

        $credit->update([
            'paid_amount' => $newPaid,
            'status'      => $status,
            'notes'       => $validated['notes'] ?? $credit->notes,
        ]);

        return response()->json([
            'success'   => true,
            'status'    => $status,
            'paid'      => $newPaid,
            'remaining' => $credit->fresh()->remaining,
            'message'   => $status === 'pagado' ? 'Crédito liquidado' : 'Abono registrado',
        ]);
    }
}
