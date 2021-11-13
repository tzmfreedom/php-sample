<?php

namespace GoParser\Ast;

class String_ extends Node {
    /**
     * @var string
     */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
