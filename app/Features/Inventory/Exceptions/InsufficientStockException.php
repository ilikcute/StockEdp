<?php

namespace App\Features\Inventory\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = 'Saldo stok tidak mencukupi untuk melakukan transaksi ini.')
    {
        parent::__construct($message, 422);
    }

    public function render($request)
    {
        return response()->api(null, $this->getMessage(), 422);
    }
}
