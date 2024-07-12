<?php

namespace Hachchadi\CmiPayment\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function storeKeyNotSpecified(): static
    {
        return new static('No store key (storeKey) has been specified. You must provide a valid store key (configured in your back office of the CMI platform).');
    }

    public static function storeKeyInvalid(): static
    {
        return new static('The store key (storeKey) provided is not valid. Please provide a store key that does not contain any spaces or an empty string.');
    }

    public static function clientIdNotSpecified(): static
    {
        return new static('No merchant ID (clientId) has been specified. You must provide a valid merchant ID (assigned by CMI).');
    }

    public static function clientIdInvalid(): static
    {
        return new static('The merchant ID (clientId) provided is not valid. Please provide a merchant ID that does not contain any spaces or an empty string.');
    }

    public static function attributeNotSpecified(string $attribute): static
    {
        return new static('No ' . $attribute . ' has been specified. Please provide it.');
    }

    public static function attributeInvalidString(string $attribute): static
    {
        return new static('The value of ' . $attribute . ' provided is not valid. Please provide a ' . $attribute . ' that does not contain any spaces or an empty string.');
    }

    public static function attributeInvalidUrl(string $attribute): static
    {
        return new static('The URL for ' . $attribute . ' provided is not valid. Please provide a valid link.');
    }

    public static function langValueInvalid(): static
    {
        return new static('The default language value is not valid. Possible values: ar, fr, en');
    }

    public static function sessionimeoutValueInvalid(): static
    {
        return new static('The session timeout (sessionTimeout) value is not valid. Please provide a valid number. The minimum allowed value is 30 seconds and the maximum is 2700 seconds.');
    }
}