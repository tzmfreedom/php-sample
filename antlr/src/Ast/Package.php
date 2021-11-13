<?php

namespace GoParser\Ast;

class Package extends Node
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
