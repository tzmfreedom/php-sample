<?php

namespace GoParser\Ast;

use GoParser\GoInterpreter;

abstract class Node
{
    public function accept(GoInterpreter $visitor)
    {
        return $visitor->visit($this);
    }
}
