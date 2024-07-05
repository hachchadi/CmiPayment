<?php

namespace Hachchadi\CmiPayment\Exceptions;

use Exception;

class InvalidRequest extends Exception
{
    public static function amountNotSpecified()
    {
        return new static("The 'amount' is not specified.");
    }

    public static function amountValueInvalid()
    {
        return new static("The 'amount' value is invalid. It must be a numeric value.");
    }

    public static function currencyNotSpecified()
    {
        return new static("The 'currency' is not specified.");
    }

    public static function currencyValueInvalid()
    {
        return new static("The 'currency' value is invalid. It must be a 3-character string.");
    }

    public static function attributeNotSpecified($attribute)
    {
        return new static("The '{$attribute}' is not specified.");
    }

    public static function attributeInvalidString($attribute)
    {
        return new static("The '{$attribute}' value is invalid. It must be a non-empty string.");
    }

    public static function emailValueInvalid()
    {
        return new static("The 'email' value is invalid.");
    }
}
