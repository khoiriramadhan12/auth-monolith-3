<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Controllers\MemberController;

Route::prefix('membership')->name('membership.')->group(function () {
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/{id}', [MemberController::class, 'show'])->name('members.show');
    Route::post('/members/{id}/restore', [MemberController::class, 'restore'])->name('members.restore');
});
