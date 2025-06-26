<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TypedTemplate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'typed-template';
    }
}