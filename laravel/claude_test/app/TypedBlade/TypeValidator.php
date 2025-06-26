<?php

namespace App\TypedBlade;

use App\TypedBlade\Exceptions\TypeMismatchException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TypeValidator
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
        'callable' => 'is_callable',
        'resource' => 'is_resource',
        'numeric' => 'is_numeric',
    ];

    public function validateTypes(array $data, array $typeDeclarations): void
    {
        foreach ($typeDeclarations as $variable => $expectedType) {
            if (!array_key_exists($variable, $data)) {
                if (!$this->isNullableType($expectedType)) {
                    continue;
                }
            }

            $value = $data[$variable] ?? null;
            
            if (!$this->isValidType($value, $expectedType)) {
                $actualType = $this->getActualType($value);
                throw new TypeMismatchException($variable, $expectedType, $actualType);
            }
        }
    }

    private function isValidType(mixed $value, string $expectedType): bool
    {
        if ($value === null && $this->isNullableType($expectedType)) {
            return true;
        }

        $expectedType = $this->normalizeType($expectedType);

        if ($this->isUnionType($expectedType)) {
            return $this->validateUnionType($value, $expectedType);
        }

        if ($this->isGenericType($expectedType)) {
            return $this->validateGenericType($value, $expectedType);
        }

        if (isset($this->typeMap[$expectedType])) {
            $checkFunction = $this->typeMap[$expectedType];
            return $checkFunction($value);
        }

        if ($this->isClassType($expectedType)) {
            return $this->validateClassType($value, $expectedType);
        }

        if ($expectedType === 'mixed') {
            return true;
        }

        return false;
    }

    private function isNullableType(string $type): bool
    {
        return str_contains($type, '|null') || str_contains($type, 'null|') || str_starts_with($type, '?');
    }

    private function normalizeType(string $type): string
    {
        $type = str_replace(['|null', 'null|'], '', $type);
        $type = ltrim($type, '?');
        return trim($type, '|');
    }

    private function isUnionType(string $type): bool
    {
        return str_contains($type, '|');
    }

    private function validateUnionType(mixed $value, string $unionType): bool
    {
        $types = explode('|', $unionType);
        
        foreach ($types as $type) {
            if ($this->isValidType($value, trim($type))) {
                return true;
            }
        }
        
        return false;
    }

    private function isGenericType(string $type): bool
    {
        return str_contains($type, '<') || str_ends_with($type, '[]');
    }

    private function validateGenericType(mixed $value, string $genericType): bool
    {
        if (str_ends_with($genericType, '[]')) {
            $baseType = str_replace('[]', '', $genericType);
            
            if (!is_array($value) && !$value instanceof Collection) {
                return false;
            }
            
            foreach ($value as $item) {
                if (!$this->isValidType($item, $baseType)) {
                    return false;
                }
            }
            
            return true;
        }

        if (preg_match('/([^<]+)<([^>]+)>/', $genericType, $matches)) {
            $containerType = $matches[1];
            $itemType = $matches[2];
            
            if ($containerType === 'Collection' && $value instanceof Collection) {
                foreach ($value as $item) {
                    if (!$this->isValidType($item, $itemType)) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }

    private function isClassType(string $type): bool
    {
        return class_exists($type) || interface_exists($type) || trait_exists($type);
    }

    private function validateClassType(mixed $value, string $expectedType): bool
    {
        if (class_exists($expectedType)) {
            return $value instanceof $expectedType;
        }

        if (interface_exists($expectedType)) {
            return $value instanceof $expectedType;
        }

        if ($expectedType === 'Model' && $value instanceof Model) {
            return true;
        }

        if ($expectedType === 'Collection' && $value instanceof Collection) {
            return true;
        }

        return false;
    }

    private function getActualType(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return gettype($value);
    }
}