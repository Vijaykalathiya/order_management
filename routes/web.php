<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return view('layouts.app');
});


Route::get('/products/import', [ProductController::class, 'showForm'])->name('products.import.form');
Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

Route::get('/order/search-product', [ProductController::class, 'showOrderForm'])->name('searchProduct');
Route::post('/print-order', [OrderController::class, 'printOrder'])->name('print.order');

Route::get('/order/report', [OrderController::class, 'showOrderDetails'])->name('order.report');
