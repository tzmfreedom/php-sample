<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TypedView extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'typed-view';
    }
}