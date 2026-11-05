<?php

declare(strict_types=1);

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index'])->name('home');

Route::prefix('wiki/{slug}')->group(function (): void {
    Route::get('/', [ArticleController::class, 'show'])->name('wiki.show');
    Route::get('/edit', [ArticleController::class, 'edit'])->name('wiki.edit');
    Route::put('/', [ArticleController::class, 'update'])->name('wiki.update');
    Route::get('/history', [ArticleController::class, 'history'])->name('wiki.history');
});
