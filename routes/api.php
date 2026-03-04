<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PhotoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('pos')->group(function () {
    Route::get('/', [ProductsController::class, 'index']);
    Route::get('/branches', [ProductsController::class, 'getBranches']);
    Route::get('/products', [ProductsController::class, 'getProducts']);
    Route::get('/categories', [ProductsController::class, 'getCategories']);
    Route::get('/customers', [ProductsController::class, 'searchCustomers']);
    Route::post('/customers', [ProductsController::class, 'storeCustomer']);
    Route::post('/sales', [ProductsController::class, 'processSale']);
    Route::get('/sales', [ProductsController::class, 'getSales']);
    Route::get('/sales/{id}', [ProductsController::class, 'getSale']);
    Route::post('/sales/{id}/cancel', [ProductsController::class, 'cancelSale']);
    Route::post('/sales/{id}/resend', [ProductsController::class, 'resendReceipt']);

    Route::get('/cash-movements',  [ProductsController::class, 'getCashMovements']);
    Route::post('/cash-movements', [ProductsController::class, 'storeCashMovement']);

    Route::get('/credits',          [ProductsController::class, 'getCredits']);
    Route::post('/credits/{id}/pay',[ProductsController::class, 'payCredit']);

    // ── Inventario CRUD + entrada de mercancía ────────────────────────
    Route::get('/products/{id}',            [ProductsController::class, 'getProduct']);
    Route::post('/products',                [ProductsController::class, 'storeProduct']);
    Route::put('/products/{id}',            [ProductsController::class, 'updateProduct']);
    Route::delete('/products/{id}',         [ProductsController::class, 'deleteProduct']);
    Route::post('/products/{id}/entrada',   [ProductsController::class, 'stockEntry']);
    Route::get('/inventory-movements',      [ProductsController::class, 'getInventoryMovements']);
});

// ─── Módulo de fotos ───────────────────────────────────────────────
Route::prefix('fotos')->group(function () {
    Route::get('/products',                    [PhotoController::class, 'getProducts']);
    Route::post('/products/{id}/photo',        [PhotoController::class, 'savePhoto']);
    Route::delete('/products/{id}/photo',      [PhotoController::class, 'deletePhoto']);
});

