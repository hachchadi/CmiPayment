<?php

namespace Hachchadi\CmiPayment\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function clientIdNotSpecified()
    {
        return new static("The configuration value for 'clientId' is not set.");
    }

    public static function clientIdInvalid()
    {
        return new static("The 'clientId' configuration value contains invalid characters.");
    }

    public static function storeKeyNotSpecified()
    {
        return new static("The configuration value for 'storeKey' is not set.");
    }

    public static function storeKeyInvalid()
    {
        return new static("The 'storeKey' configuration value contains invalid characters.");
    }

    public static function attributeNotSpecified($attribute)
    {
        return new static("The configuration value for '{$attribute}' is not set.");
    }

    public static function attributeInvalidString($attribute)
    {
        return new static("The '{$attribute}' configuration value contains invalid characters.");
    }

    public static function attributeInvalidUrl($attribute)
    {
        return new static("The '{$attribute}' configuration value is not a valid URL.");
    }

    public static function langValueInvalid()
    {
        return new static("The 'lang' configuration value is invalid. Allowed values are 'fr', 'ar', and 'en'.");
    }

    public static function sessionimeoutValueInvalid()
    {
        return new static("The 'sessionTimeout' configuration value must be between 30 and 2700 seconds.");
    }
}
