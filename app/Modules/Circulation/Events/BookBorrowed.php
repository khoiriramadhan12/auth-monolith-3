<?php

namespace Modules\Circulation\Events;

use Modules\Circulation\Models\Borrowing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookBorrowed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Borrowing $borrowing
    ) {}
}
