<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;



class ProductsController extends Controller
{
    //
     protected $saleService;
    
    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }
    
    /**
     * Mostrar vista principal del POS
     */
    public function index()
    {
        return view('welcome');
    }
    public function getBranches(){
        $branches = \App\Models\BranchModel::all();
        return response()->json($branches); 
    }
    /**
     * Obtener productos para el POS
     */
  public function getProducts(Request $request)
{
    $query = Product::with('category')
        ->where('active', 1);

    // Filtro por búsqueda
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    // Filtro por categoría
    if ($request->has('category') && $request->category !== 'Todas') {
        $query->whereHas('category', function ($q) use ($request) {
            $q->where('name', $request->category);
        });
    }

    $products = $query->get()->map(function ($product) {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'category' => optional($product->category)->name ?? 'Sin categoría',
            'size' => $product->size,
            'stock' => $product->stock,
            'price' => (float) $product->sale_price,
            'cost' => (float) $product->cost_price,
        ];
    });

    return response()->json($products);
}

    
    /**
     * Obtener categorías
     */
    public function getCategories()
    {
        $categories = Category::where('active', true)
            ->orderBy('name')
            ->pluck('name');
            
        return response()->json(['Todas', ...$categories]);
    }
    
    /**
     * Procesar venta
     */
    // ProductsController.php

   
public function processSale(Request $request)
{
    $validated = $request->validate([
        'customer_name' => 'nullable|string|max:255',
        'customer_email' => 'nullable|email',
        'payment_method' => 'required|in:efectivo,tarjeta,transferencia,mixto',
        'amount_paid' => 'required|numeric|min:0',
        'change_amount' => 'nullable|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'total' => 'required|numeric|min:0',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.total' => 'required|numeric|min:0',
        'branch_id' => 'required|exists:branches,id',
    ]);

    try {
        $sale = $this->saleService->processSale([
            'customer_name' => $validated['customer_name'] ?? 'Cliente',
            'customer_id' => null,
            'subtotal' => $validated['subtotal'],
            'discount' => $validated['discount'] ?? 0,
            'total' => $validated['total'],
            'payment_method' => $validated['payment_method'],
            'amount_paid' => $validated['amount_paid'],
            'change_amount' => $validated['change_amount'] ?? 0,
            'items' => $validated['items'],
            'branch_id' => $validated['branch_id'],
        ]);

        $emailSent = false;

        if (!empty($validated['customer_email'])) {
            try {
                $customer = \App\Models\Customer::firstOrCreate(
                    ['email' => $validated['customer_email']],
                    ['name' => $validated['customer_name'] ?? 'Cliente']
                );
                $sale->update(['customer_id' => $customer->id]);
                $sale = $sale->load('details.product', 'branch');
                 $pdf = Pdf::loadView('emails.pdf-comprobant', compact('sale')); // aquí pones tu vista del PDF
        $pdfData = $pdf->output();

                \Mail::to($validated['customer_email'])->send(new \App\Mail\SaleReceipt($sale, $pdfData));
                $emailSent = true;
                $sale->update(['email_sent' => true]);
            } catch (\Exception $e) {
                \Log::error("Error enviando comprobante: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Venta procesada exitosamente',
            'data' => [
                'invoice_number' => $sale->invoice_number,
                'customer_name' => $sale->customer->name ?? 'Cliente',
                'total' => $sale->total,
                'change' => $sale->change_amount,
                'email_sent' => $emailSent,
            ]
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Error al procesar venta: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar venta: ' . $e->getMessage()
        ], 422);
    }
}


    /**
     * Obtener historial de ventas
     */
    public function getSales(Request $request)
    {
        $query = Sale::with(['user', 'customer', 'details.product'])
            ->orderBy('sale_date', 'desc');
        
        // Filtro por fecha
        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        
        // Filtro por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $sales = $query->paginate($request->get('per_page', 20));
        
        return response()->json($sales);
    }
    
    /**
     * Obtener detalle de venta
     */
    public function getSale($id)
    {
        $sale = Sale::with(['user', 'customer', 'details.product.category'])
            ->findOrFail($id);
            
        
        return response()->json($sale);
    }
    
    /**
     * Cancelar venta
     */
    public function cancelSale($id)
    {
        try {
            $sale = Sale::findOrFail($id);
            
            if ($sale->status === 'cancelada') {
                return response()->json([
                    'success' => false,
                    'message' => 'La venta ya está cancelada'
                ], 422);
            }
            
            $this->saleService->cancelSale($sale);
            
            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar venta: ' . $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Reenviar comprobante
     */
    public function resendReceipt($id, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);
        
        try {
            $sale = Sale::findOrFail($id);
            
            // Actualizar o crear cliente
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => $validated['email']],
                ['name' => 'Cliente']
            );
            
            $sale->update(['customer_id' => $customer->id]);
            
            $sent = $this->saleService->sendReceipt($sale);
            
            if ($sent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante enviado exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el comprobante'
                ], 422);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }
    public function dashboard(Request $request)
{
    if ($request->ajax()) {
        return response()->json($this->getDashboardData($request));
    }

    return view('dashboard');
}
    /**
     * Dashboard de estadísticas
     */
public function getDashboardData(Request $request)
{
    $branchId = $request->get('branch_id');

    $startDate = $request->get('date_from', today());
    $endDate = $request->get('date_to', today()->copy()->addDay());

    $startDate = Carbon::parse($startDate)->startOfDay();
    $endDate = Carbon::parse($endDate)->endOfDay();

    $salesQuery = Sale::where('status', 'completada')
        ->whereBetween('sale_date', [$startDate, $endDate])
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

    $stats = [
        'total_sales' => $salesQuery->sum('total'),

        'transactions_count' => $salesQuery->count(),

        'average_ticket' => $salesQuery->avg('total'),

        'low_stock_products' => Product::whereColumn('stock', '<=', 'min_stock')->count(),

        'daily_sales' => Sale::where('status', 'completada')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('DATE(sale_date) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get(),

        'top_products' => DB::table('sale_details')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completada')
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->selectRaw('products.name, SUM(sale_details.quantity) as total_sold, SUM(sale_details.total) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get(),

        'sales_by_method' => $salesQuery
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get(),

        'sales_detail' => DB::table('sales')
            ->join('sale_details', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', 'completada')
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->select(
                'sales.id',
                'sales.sale_date',
                'sales.payment_method',
                DB::raw('SUM(sale_details.quantity) as total_quantity'),
                DB::raw('SUM(sale_details.total) as total_amount'),
                DB::raw('GROUP_CONCAT(CONCAT(products.name, " (x", sale_details.quantity, ")") SEPARATOR ", ") as products')
            )
            ->groupBy('sales.id', 'sales.sale_date', 'sales.payment_method')
            ->orderBy('sales.sale_date', 'desc')
            ->get(),
    ];

    return response()->json($stats);
}


    public function inventoryView()
{
    $categories = \App\Models\Category::all();
    $products = \App\Models\Product::all(); 
    return view('inventory', compact('categories', 'products'));
}
    public function store(Request $request)
{
    $validated = $request->validate([
        'code' => 'required|string|max:50|unique:products,code',
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:200',
        'size' => 'nullable|string|max:50',
        'stock' => 'required|integer|min:0',
        'min_stock' => 'required|integer|min:0',
        'cost_price' => 'required|numeric|min:0',
        'estimated_price' => 'nullable|numeric|min:0',
        'sale_price' => 'required|numeric|min:0',
        'description' => 'nullable|string',
        'image' => 'nullable|string',
        'active' => 'boolean',
    ]);

    $product = Product::create($validated);

    $products = \App\Models\Product::all();

    $categories = \App\Models\Category::all();
    return view('inventory', compact('categories', 'products'));
}
 public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $products = Product::all();
        return view('inventory', compact('products', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'code' => "required|unique:products,code,{$product->id}",
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
        ]);
        $categories = Category::all();
        $product->update($request->all());

        $products = Product::all(); 

        return view('inventory', compact('categories', 'products'))->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

          $categories = \App\Models\Category::all();
    $products = \App\Models\Product::all(); 
    return view('inventory', compact('categories', 'products'));
    }



}
