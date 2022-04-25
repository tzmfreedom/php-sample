<?php

namespace App;

/**
 * @property string $hoge
 */
class User
{
    /**
     * Begin querying the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public static function query()
    {
        return (new static)->newQuery();
    }
}
