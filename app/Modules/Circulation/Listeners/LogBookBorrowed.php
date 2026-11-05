<?php

namespace Modules\Circulation\Listeners;

use Modules\Circulation\Events\BookBorrowed;
use Illuminate\Support\Facades\Log;

class LogBookBorrowed
{
    public function handle(BookBorrowed $event): void
    {
        $borrowing = $event->borrowing;
        $itemsCount = $borrowing->details->count();

        Log::info("BookBorrowed Event Triggered: Kode Peminjaman {$borrowing->borrow_code} berhasil dibuat untuk Member ID {$borrowing->member_id} dengan {$itemsCount} buku.");
    }
}
