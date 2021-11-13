<?php

namespace GoParser\Ast;

class File extends Node
{
    /**
     * @var Package[]
     */
    public $packages;

    /**
     * @var Function_[]
     */
    public $functions;
}
