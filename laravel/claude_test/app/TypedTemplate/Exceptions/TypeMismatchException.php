<?php

namespace App\TypedTemplate\Exceptions;

use Exception;

class TypeMismatchException extends Exception
{
    public function __construct(string $variableName, string $expectedType, string $actualType)
    {
        parent::__construct("Type mismatch for variable '{$variableName}': expected {$expectedType}, got {$actualType}");
    }
}