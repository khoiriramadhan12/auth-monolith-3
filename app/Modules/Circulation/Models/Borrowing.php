<?php

namespace Modules\Circulation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Membership\Models\Member;

class Borrowing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'borrow_code',
        'member_id',
        'borrow_date',
        'due_date',
        'return_date',
        'status',
        'total_fine',
        'notes',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'total_fine' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(BorrowingDetail::class, 'borrowing_id');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class, 'borrowing_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'borrowed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'borrowed')->where('due_date', '<', now()->startOfDay());
    }
}
