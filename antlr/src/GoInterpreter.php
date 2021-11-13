<?php

namespace GoParser;

use GoParser\Ast\Arguments;
use GoParser\Ast\BinaryExpression;
use GoParser\Ast\Decimal;
use GoParser\Ast\File;
use GoParser\Ast\Function_;
use GoParser\Ast\Identifier;
use GoParser\Ast\Integer;
use GoParser\Ast\MethodCall;
use GoParser\Ast\Nil;
use GoParser\Ast\Node;
use GoParser\Ast\Package;
use GoParser\Ast\Statement\Expression;
use GoParser\Ast\String_;
use GoParser\Context\FunctionDeclContext;
use GoParser\Value\IntegerValue;
use GoParser\Value\StringValue;

/**
 * This class provides an empty implementation of {@see GoParserVisitor},
 * which can be extended to create a visitor which only needs to handle a subset
 * of the available methods.
 */
class GoInterpreter
{
    public function visit(Node $node)
    {
        if ($node instanceof File) {
            foreach ($node->functions['main']->statements as $statement) {
                $statement->accept($this);
            }
        }
        if ($node instanceof Package) {
            return null;
        }
        if ($node instanceof Function_) {
            return null;
        }
        if ($node instanceof Expression) {
            return $node->expression->accept($this);
        }
        if ($node instanceof MethodCall) {
            $arguments = [];
            foreach ($node->arguments as $argument) {
                $arguments[] = $argument->accept($this);
            }
            $receiver = $node->expr->accept($this);
            if ($receiver === 'fmt.Println') {
                echo $arguments[0]->value . PHP_EOL;
            }
        }
        if ($node instanceof BinaryExpression) {
            $left = $node->left->accept($this);
            $right = $node->left->accept($this);
            switch ($node->op) {
                case '+':
                    return new IntegerValue($left->value + $right->value);
                case '-':
                    return new IntegerValue($left->value - $right->value);
                case '*':
                    return new IntegerValue($left->value * $right->value);
                case '/':
                    return new IntegerValue($left->value / $right->value);
                case '%':
                    return new IntegerValue($left->value % $right->value);
            }
        }
        if ($node instanceof Identifier) {
            if ($node->child === null) {
                return $node->name;
            }
            return $node->name . '.' . $node->child->accept($this);
        }
        if ($node instanceof String_) {
            return new StringValue($node->value);
        }
        if ($node instanceof Integer) {
            return new IntegerValue($node->value);
        }
        return $node;
    }
}
