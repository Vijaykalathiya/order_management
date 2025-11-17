<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect()->route('searchProduct');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/products/import', [ProductController::class, 'showForm'])->name('products.import.form');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/products/update', [ProductController::class, 'updateAjax'])->name('products.update');
    Route::post('/products/delete', [ProductController::class, 'deleteAjax'])->name('products.delete');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    
    
    Route::get('/order/search-product', [ProductController::class, 'showOrderForm'])->name('searchProduct');
    Route::post('/print-order', [OrderController::class, 'printOrder'])->name('print.order');
    
    // Route::get('/order/report', [OrderController::class, 'showOrderDetails'])->name('order.report');
    Route::get('/order/report', [OrderController::class, 'showOrderDetails'])->name('order.report');
    Route::get('/orders/data', [OrderController::class, 'getData'])->name('orders.data');
    Route::get('/orders/analytics', [OrderController::class, 'getAnalytics'])->name('orders.analytics');
    
    Route::post('/orders/delete-range', [OrderController::class, 'deleteRange'])->name('orders.deleteRange');

    Route::post('/products/store', [ProductController::class, 'store'])->name('products.store');

    Route::get('/orders/filter', [OrderController::class, 'filter'])->name('orders.filter');
    Route::get('/orders/export-all', [OrderController::class, 'exportAll'])->name('orders.exportAll');
    Route::get('/orders/analysis/export', [OrderController::class, 'exportAnalysis'])
    ->name('orders.analysis.export');



});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');