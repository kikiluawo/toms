<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/select-company', [CompanyContextController::class, 'select'])->name('context.select');
    Route::post('/select-company', [CompanyContextController::class, 'store'])->name('context.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('users', UserController::class)
        ->except('show')
        ->middleware('menu:users');

    Route::middleware('menu:structure')->prefix('/structure')->group(function () {
        Route::get('/', [StructureController::class, 'index'])->name('structure.index');
        Route::post('/departments', [StructureController::class, 'storeDepartment'])->name('structure.departments.store');
        Route::post('/positions', [StructureController::class, 'storePosition'])->name('structure.positions.store');
        Route::delete('/departments/{department}', [StructureController::class, 'destroyDepartment'])->name('structure.departments.destroy');
        Route::delete('/positions/{position}', [StructureController::class, 'destroyPosition'])->name('structure.positions.destroy');
    });

    Route::middleware('menu:companies')->prefix('/companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/', [CompanyController::class, 'store'])->name('companies.store');
        Route::patch('/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('/{company}/branches', [CompanyController::class, 'storeBranch'])->name('companies.branches.store');
        Route::delete('/branches/{branch}', [CompanyController::class, 'destroyBranch'])->name('companies.branches.destroy');
    });

    Route::middleware('menu:catalog')->prefix('/catalog')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
        Route::post('/', [CatalogController::class, 'store'])->name('catalog.store');
        Route::put('/{product}', [CatalogController::class, 'update'])->name('catalog.update');
        Route::patch('/{product}/active', [CatalogController::class, 'toggleActive'])->name('catalog.active');
        Route::delete('/{product}', [CatalogController::class, 'destroy'])->name('catalog.destroy');
    });

    Route::middleware('menu:suppliers')->prefix('/suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    Route::middleware('menu:orders')->group(function () {
        Route::get('/orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [PurchaseOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/edit', [PurchaseOrderController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{order}', [PurchaseOrderController::class, 'update'])->name('orders.update');
        Route::post('/orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('orders.submit');
        Route::delete('/orders/{order}', [PurchaseOrderController::class, 'destroy'])->name('orders.destroy');
        Route::get('/orders/{order}/receipts/create', [PurchaseReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/orders/{order}/receipts', [PurchaseReceiptController::class, 'store'])->name('receipts.store');
    });

    Route::middleware('menu:stock')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::post('/stock/adjustments', [StockController::class, 'storeAdjustment'])->name('stock.adjustments.store');
    });
});

require __DIR__.'/auth.php';
