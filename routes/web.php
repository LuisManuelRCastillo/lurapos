<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Mail\SaleReceipt;
use App\Models\Sale;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
Route::get('/dashboard/data', [ProductsController::class, 'getDashboardData'])->name('dashboard.data');

});
// routes/web.php - Para la vista principal
Route::get('/pos', [ProductsController::class, 'index'])->name('pos.index');
Route::get('/checkout', function() {
    return view('pos.checkout');
});
Route::get('/pos/receipt', function() {
    return view('pos.receipt');
})->name('pos.receipt');
Route::get('/inventario', [ProductsController::class, 'inventoryView'])->name('inventory.view');

Route::post('/products/store', [ProductsController::class, 'store'])->name('inventory.store');
Route::get('/inventory/{product}/edit', [ProductsController::class, 'edit'])->name('inventory.edit');
Route::put('/inventory/{product}', [ProductsController::class, 'update'])->name('inventory.update');
Route::delete('/inventory/{product}', [ProductsController::class, 'destroy'])->name('inventory.destroy');



// Módulo de gestión de fotos
Route::get('/fotos', [ProductsController::class, 'fotosView'])->name('fotos.view');

Route::get('/test-email', function () {
    $sale = Sale::latest()->first(); // o crea una venta dummy si quieres

    return (new SaleReceipt($sale))->render();
});

