<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Controllers\BookController;

Route::prefix('catalog')->name('catalog.')->group(function () {
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{id}', [BookController::class, 'show'])->name('books.show');
    Route::post('/books/{id}/restore', [BookController::class, 'restore'])->name('books.restore');
});
