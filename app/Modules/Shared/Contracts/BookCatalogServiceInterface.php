<?php

namespace Modules\Shared\Contracts;

interface BookCatalogServiceInterface
{
    public function getBookForBorrowing(int $bookId): ?array;
    public function isStockAvailable(int $bookId, int $quantity = 1): bool;
    public function reduceStock(int $bookId, int $quantity = 1): bool;
    public function restoreStock(int $bookId, int $quantity = 1): bool;
}
