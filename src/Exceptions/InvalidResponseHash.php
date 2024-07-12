<?php

namespace Hachchadi\CmiPayment\Exceptions;

use Exception;

class InvalidResponseHash extends Exception
{
    public static function hashMismatch(): static
    {
        return new static('The hash value does not match the expected value.');
    }
}
