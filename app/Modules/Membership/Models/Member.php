<?php

namespace Modules\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'member_code',
        'name',
        'email',
        'phone',
        'address',
        'status',
        'membership_expiry',
    ];

    protected $casts = [
        'membership_expiry' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'member_role_pivot', 'member_id', 'role_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->membership_expiry === null || $this->membership_expiry >= now()->startOfDay());
    }
}
