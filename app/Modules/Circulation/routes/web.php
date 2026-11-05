<?php

use Illuminate\Support\Facades\Route;
use Modules\Circulation\Controllers\BorrowingController;

Route::prefix('circulation')->name('circulation.')->group(function () {
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::get('/borrowings/{id}', [BorrowingController::class, 'show'])->name('borrowings.show');
    Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
    Route::post('/borrowings/{id}/return', [BorrowingController::class, 'returnBooks'])->name('borrowings.return');
    Route::post('/borrowings/{id}/restore', [BorrowingController::class, 'restore'])->name('borrowings.restore');
});
