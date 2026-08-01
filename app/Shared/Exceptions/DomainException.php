<?php

namespace App\Shared\Exceptions;

use Exception;

class DomainException extends Exception
{
    public function __construct(
        string $message,
        protected int $status = 422,
        protected array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
