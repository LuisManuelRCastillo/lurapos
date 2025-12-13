<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('pos')->group(function () {
    Route::get('/', [ProductsController::class, 'index']);
    Route::get('/branches', [ProductsController::class, 'getBranches']);
    Route::get('/products', [ProductsController::class, 'getProducts']);
    Route::get('/categories', [ProductsController::class, 'getCategories']);
    Route::post('/sales', [ProductsController::class, 'processSale']);
    Route::get('/sales', [ProductsController::class, 'getSales']);
    Route::get('/sales/{id}', [ProductsController::class, 'getSale']);
    Route::post('/sales/{id}/cancel', [ProductsController::class, 'cancelSale']);
    Route::post('/sales/{id}/resend', [ProductsController::class, 'resendReceipt']);

    Route::post('/pos/products', [ProductController::class, 'store']);
});

