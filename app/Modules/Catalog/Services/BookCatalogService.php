<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\Book;
use Modules\Shared\Contracts\BookCatalogServiceInterface;

class BookCatalogService implements BookCatalogServiceInterface
{
    public function getBookForBorrowing(int $bookId): ?array
    {
        $book = Book::find($bookId);
        if (!$book) {
            return null;
        }

        return [
            'id' => $book->id,
            'title' => $book->title,
            'isbn' => $book->isbn,
            'stock' => $book->stock,
            'is_available' => $book->is_available,
        ];
    }

    public function isStockAvailable(int $bookId, int $quantity = 1): bool
    {
        $book = Book::find($bookId);
        return $book !== null && $book->isStockAvailable($quantity);
    }

    public function reduceStock(int $bookId, int $quantity = 1): bool
    {
        $book = Book::lockForUpdate()->find($bookId);
        if (!$book || !$book->isStockAvailable($quantity)) {
            return false;
        }

        $book->decrement('stock', $quantity);
        if ($book->stock === 0) {
            $book->update(['is_available' => false]);
        }

        return true;
    }

    public function restoreStock(int $bookId, int $quantity = 1): bool
    {
        $book = Book::lockForUpdate()->find($bookId);
        if (!$book) {
            return false;
        }

        $book->increment('stock', $quantity);
        if ($book->stock > 0 && !$book->is_available) {
            $book->update(['is_available' => true]);
        }

        return true;
    }
}
