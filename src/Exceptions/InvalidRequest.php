<?php

namespace Hachchadi\CmiPayment\Exceptions;

use Exception;

class InvalidRequest extends Exception
{
    public static function amountNotSpecified(): static
    {
        return new static('No amount has been specified. Please provide the transaction amount.');
    }

    public static function amountValueInvalid(): static
    {
        return new static('The entered transaction amount is invalid. Please provide a numeric value for the amount without any currency symbols. Use "." or "," for the decimal separator.');
    }

    public static function currencyNotSpecified(): static
    {
        return new static('No currency code has been specified. Please provide an ISO code for the transaction currency.');
    }

    public static function currencyValueInvalid(): static
    {
        return new static('The provided currency code is invalid. Please provide an ISO 4217 numeric code for the currency. ISO code for MAD: 504');
    }

    public static function attributeNotSpecified(string $attribute): static
    {
        return new static('No ' . $attribute . ' has been specified. Please provide it.');
    }

    public static function attributeInvalidString(string $attribute): static
    {
        return new static('The value of ' . $attribute . ' provided is not valid. Please provide a ' . $attribute . ' that does not contain any spaces or an empty string.');
    }

    public static function emailValueInvalid(): static
    {
        return new static('The provided customer email address is not a valid email address.');
    }
}
