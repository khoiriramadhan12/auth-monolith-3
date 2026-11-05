<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Membership\Models\Member;

class Fine extends Model
{
    protected $fillable = [
        'borrowing_id',
        'member_id',
        'days_late',
        'fine_amount',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'days_late' => 'integer',
        'fine_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
