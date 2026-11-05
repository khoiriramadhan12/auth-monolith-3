<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    protected $fillable = ['name', 'slug', 'address', 'phone'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
