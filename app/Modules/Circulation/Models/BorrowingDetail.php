<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingDetail extends Model
{
    protected $fillable = [
        'borrowing_id',
        'book_id',
        'book_title',
        'book_isbn',
        'quantity',
        'status',
        'returned_at',
    ];

    protected $casts = [
        'returned_at' => 'date',
        'quantity' => 'integer',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }
}
