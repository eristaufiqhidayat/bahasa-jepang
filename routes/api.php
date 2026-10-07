<?php

use App\Http\Controllers\LearningApiController as Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    Route::get('tracks', [Api::class, 'tracks']);
    Route::get('lessons', [Api::class, 'lessons']);
    Route::get('lessons/{lesson}', [Api::class, 'lesson']);
    Route::get('characters', [Api::class, 'characters']);
    Route::get('vocabularies', [Api::class, 'vocabularies']);
    Route::post('questions/{question}/answer', [Api::class, 'answer']);
});
