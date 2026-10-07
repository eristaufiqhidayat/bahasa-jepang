<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LearningWebController;
use App\Http\Controllers\MaterialAnswerAdminController;
use App\Http\Controllers\MaterialChatController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\VirtualTestAdminController;
use App\Http\Controllers\VirtualTestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningWebController::class, 'index'])->name('learning.home');
Route::get('/belajar', [LearningWebController::class, 'index'])->name('learning');
Route::get('/belajar/audio/{type}/{id}', [LearningWebController::class, 'audio'])->whereNumber('id')->name('learning.audio');
Route::post('/belajar/questions/{question}/answer', [LearningWebController::class, 'answer'])->middleware('throttle:60,1');
Route::prefix('belajar/tanya-materi')->middleware('throttle:30,1')->group(function () {
    Route::get('/', [MaterialChatController::class, 'history']);
    Route::post('/', [MaterialChatController::class, 'ask']);
    Route::post('/{message}/report', [MaterialChatController::class, 'report']);
});
Route::prefix('belajar/virtual-tests')->middleware('throttle:120,1')->group(function () {
    Route::post('/templates/{template}/start', [VirtualTestController::class, 'start']);
    Route::get('/attempts/{attempt}', [VirtualTestController::class, 'show']);
    Route::post('/attempts/{attempt}/answers', [VirtualTestController::class, 'save']);
    Route::post('/attempts/{attempt}/finish', [VirtualTestController::class, 'finish']);
});
Route::middleware(['auth', 'admin'])->prefix('pratinjau')->group(function () {
    Route::get('/', [LearningWebController::class, 'preview'])->name('learning.preview');
    Route::get('/audio/{type}/{id}', [LearningWebController::class, 'previewAudio'])->whereNumber('id')->name('learning.preview.audio');
    Route::post('/questions/{question}/answer', [LearningWebController::class, 'previewAnswer'])->middleware('throttle:60,1');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [MaterialController::class, 'dashboard'])->name('dashboard');
    Route::resource('material-answers', MaterialAnswerAdminController::class)->except('show');
    Route::post('/chat-reports/{report}/resolve', [MaterialAnswerAdminController::class, 'resolve'])->name('material-chat.resolve');
    Route::resource('virtual-tests', VirtualTestAdminController::class)->except('show');
    Route::get('/{type}', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/{type}/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::post('/{type}', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/{type}/{id}/edit', [MaterialController::class, 'edit'])->whereNumber('id')->name('materials.edit');
    Route::put('/{type}/{id}', [MaterialController::class, 'update'])->whereNumber('id')->name('materials.update');
    Route::delete('/{type}/{id}', [MaterialController::class, 'destroy'])->whereNumber('id')->name('materials.destroy');
});
