<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'isbn',
        'author_id',
        'publisher_id',
        'description',
        'published_year',
        'stock',
        'shelf_location',
        'is_available',
    ];

    protected $casts = [
        'published_year' => 'integer',
        'stock' => 'integer',
        'is_available' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_category', 'book_id', 'category_id')
            ->withPivot('added_at');
    }

    // Query Scopes
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->where('is_available', true);
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('isbn', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%")
              ->orWhereHas('author', fn($aq) => $aq->where('name', 'like', "%{$keyword}%"));
        });
    }

    public function scopeCategory(Builder $query, int|string $categoryIdentifier): Builder
    {
        return $query->whereHas('categories', function ($q) use ($categoryIdentifier) {
            if (is_numeric($categoryIdentifier)) {
                $q->where('book_categories.id', $categoryIdentifier);
            } else {
                $q->where('book_categories.slug', $categoryIdentifier);
            }
        });
    }

    public function isStockAvailable(int $quantity = 1): bool
    {
        return $this->stock >= $quantity && $this->is_available;
    }
}
