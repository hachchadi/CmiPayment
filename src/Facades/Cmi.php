<?php

namespace Hachchadi\CmiPayment\Facades;

use Illuminate\Support\Facades\Facade;

class Cmi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Hachchadi\CmiPayment\CmiClient::class;
    }
}
