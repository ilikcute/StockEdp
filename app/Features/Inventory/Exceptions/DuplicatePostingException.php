<?php

namespace App\Features\Inventory\Exceptions;

use Exception;

class DuplicatePostingException extends Exception
{
    public function __construct(string $message = 'Transaksi ini sudah diposting dan tidak dapat diproses ulang.')
    {
        parent::__construct($message, 409);
    }
}
