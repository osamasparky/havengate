<?php

namespace App\Exceptions;

use RuntimeException;

/** User-facing booking failure. Message is a translation key. */
class BookingException extends RuntimeException
{
    public function __construct(public readonly string $key, public readonly array $replace = [])
    {
        parent::__construct(__($key, $replace));
    }
}
