<?php

namespace GoParser\Ast\Statement;

use GoParser\Ast\Node;

class Expression extends Statement
{
    public $expression;

    public function __construct(Node $expression)
    {
        $this->expression = $expression;
    }
}
