<?php

namespace Illuminate\Support;

/**
 * @template TKey
 * @template TValue
 * @implements \ArrayAccess<TKey, TValue>
 * @implements Enumerable<TKey, TValue>
 */
class Collection implements \ArrayAccess, Enumerable
{
    /**
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return TValue|null
     */
    public function first(callable $callback = null, $default = null){}

    /**
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return TValue|null
     */
    public function last(callable $callback = null, $default = null){}


    /**
     * @param  mixed  $key
     * @param  mixed  $default
     * @return TValue|null
     */
    public function get($key, $default = null) {}

    /**
     * @return TValue|null
     */
    public function pop() {}

    /**
     * @param  mixed  $key
     * @param  mixed  $default
     * @return TValue|null
     */
    public function pull($key, $default = null) {}

    /**
     * @param mixed $value
     * @param bool  $strict
     * @return TKey|false
     */
    public function search($value, $strict = false) {}

    /**
     * @return TValue|null
     */
    public function shift() {}
}
