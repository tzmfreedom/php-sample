<?php

namespace App\TypedTemplate\Compiler;

use App\TypedTemplate\Exceptions\TypeMismatchException;

class TypeChecker
{
    private array $typeMap = [
        'string' => 'is_string',
        'int' => 'is_int',
        'integer' => 'is_int',
        'float' => 'is_float',
        'double' => 'is_float',
        'bool' => 'is_bool',
        'boolean' => 'is_bool',
        'array' => 'is_array',
        'object' => 'is_object',
        'null' => 'is_null',
    ];

    public function validateTypes(array $data, array $typeDeclarations): void
    {
        foreach ($typeDeclarations as $variable => $expectedType) {
            if (!array_key_exists($variable, $data)) {
                continue;
            }

            $value = $data[$variable];
            if (!$this->isValidType($value, $expectedType)) {
                $actualType = gettype($value);
                if (is_object($value)) {
                    $actualType = get_class($value);
                }
                
                throw new TypeMismatchException($variable, $expectedType, $actualType);
            }
        }
    }

    private function isValidType(mixed $value, string $expectedType): bool
    {
        if ($value === null && str_ends_with($expectedType, '|null')) {
            return true;
        }

        $expectedType = str_replace('|null', '', $expectedType);

        if (isset($this->typeMap[$expectedType])) {
            $checkFunction = $this->typeMap[$expectedType];
            return $checkFunction($value);
        }

        if (class_exists($expectedType)) {
            return $value instanceof $expectedType;
        }

        if (interface_exists($expectedType)) {
            return $value instanceof $expectedType;
        }

        if ($expectedType === 'mixed') {
            return true;
        }

        if (str_contains($expectedType, '[]')) {
            $baseType = str_replace('[]', '', $expectedType);
            if (!is_array($value)) {
                return false;
            }
            
            foreach ($value as $item) {
                if (!$this->isValidType($item, $baseType)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}