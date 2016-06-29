<?php

namespace App\Ruian;

use Illuminate\Support\Facades\Facade;

class RuianFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Ruian\Ruian::class;
    }

}
