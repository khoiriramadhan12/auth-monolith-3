<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/library/books', [\Modules\Catalog\Controllers\BookController::class, 'index'])->name('library.books.index');
    Route::get('/library/borrowings', [\Modules\Circulation\Controllers\BorrowingController::class, 'index'])->name('library.borrowings.index');
    Route::post('/library/borrowings', [\Modules\Circulation\Controllers\BorrowingController::class, 'store'])->name('library.borrowings.store');
    Route::post('/library/borrowings/{id}/return', [\Modules\Circulation\Controllers\BorrowingController::class, 'returnBooks'])->name('library.borrowings.return');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
