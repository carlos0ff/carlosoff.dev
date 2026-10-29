<?php

namespace App\Services\Operations;

use RuntimeException;

class CommandNotAllowedException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $report
     */
    public function __construct(string $message, public ?array $report = null)
    {
        parent::__construct($message);
    }
}
