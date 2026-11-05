<?php

namespace Modules\Shared\Exceptions;

use Exception;

class InsufficientBookStockException extends Exception
{
    public function __construct(string $bookTitle, int $currentStock, int $requestedQuantity = 1)
    {
        parent::__construct("Stok buku '{$bookTitle}' tidak mencukupi. Tersedia: {$currentStock}, Diminta: {$requestedQuantity}.");
    }
}
