<?php

namespace Modules\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'member_roles';

    protected $fillable = ['name', 'slug', 'max_borrow_days', 'max_borrow_books'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_role_pivot', 'role_id', 'member_id');
    }
}
