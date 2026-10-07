<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [MaterialController::class, 'dashboard'])->name('dashboard');
    Route::get('/{type}', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/{type}/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::post('/{type}', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/{type}/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id')->name('materials.edit');
    Route::put('/{type}/{id}', [MaterialController::class, 'update'])->whereNumber('id')->name('materials.update');
    Route::delete('/{type}/{id}', [MaterialController::class, 'destroy'])->whereNumber('id')->name('materials.destroy');
});
