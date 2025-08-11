<?php

namespace App\TypeScript;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;
use Spatie\LaravelTypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\Optional;

class CustomDtoTransformer extends DtoTransformer
{
    protected function transformProperties(ReflectionClass $class, MissingSymbolsCollection $missingSymbols): string
    {
        $isClassOptional = ! empty($class->getAttributes(Optional::class));
        $nullablesAreOptional = $this->config->shouldConsiderNullAsOptional();

        return array_reduce(
            $this->resolveProperties($class),
            function (string $carry, ReflectionProperty $property) use ($isClassOptional, $missingSymbols, $nullablesAreOptional) {
                $isHidden = ! empty($property->getAttributes(Hidden::class));

                if ($isHidden) {
                    return $carry;
                }

                $isOptional = $isClassOptional
                    || ! empty($property->getAttributes(Optional::class))
                    || ($property->getType()?->allowsNull() && $nullablesAreOptional);

                // array shapeの変換を試行
                $arrayShapeType = $this->transformArrayShape($property);
                if ($arrayShapeType !== null) {
                    $propertyName = $this->transformPropertyName($property, $missingSymbols);
                    
                    return $isOptional
                        ? "{$carry}{$propertyName}?: {$arrayShapeType};" . PHP_EOL
                        : "{$carry}{$propertyName}: {$arrayShapeType};" . PHP_EOL;
                }

                // 標準のreflectionToTypeScriptを使用
                $transformed = $this->reflectionToTypeScript(
                    $property,
                    $missingSymbols,
                    $isOptional,
                    ...$this->typeProcessors()
                );

                if ($transformed === null) {
                    return $carry;
                }

                $propertyName = $this->transformPropertyName($property, $missingSymbols);

                return $isOptional
                    ? "{$carry}{$propertyName}?: {$transformed};" . PHP_EOL
                    : "{$carry}{$propertyName}: {$transformed};" . PHP_EOL;
            },
            ''
        );
    }

    private function transformArrayShape(ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();
        if (!$docComment) {
            return null;
        }

        try {
            $config = new ParserConfig([]);
            $constExprParser = new ConstExprParser($config);
            $lexer = new Lexer($config);
            $typeParser = new TypeParser($config, $constExprParser);

            // PHPDocコメントから@varの型を抽出（複数行対応）
            if (preg_match('/@var\s+(.+?)(?=\*\/|$|\*\s*@)/s', $docComment, $matches)) {
                $typeString = $matches[1];

                // DocBlock内の*を削除し、改行を処理
                $typeString = preg_replace('/\*\s*/', '', $typeString);
                $typeString = preg_replace('/\s+/', ' ', $typeString);
                $typeString = trim($typeString);

                // デバッグ用ログ
                error_log("Parsed type string: " . $typeString);

                $tokens = $lexer->tokenize($typeString);
                $tokenIterator = new TokenIterator($tokens);

                $typeNode = $typeParser->parse($tokenIterator);

                return $this->convertTypeNodeToTypeScript($typeNode);
            }
        } catch (\Exception $e) {
            // 解析に失敗した場合はnullを返す
        }

        return null;
    }

    private function parseArrayShapeDefinition(string $shapeDefinition): string
    {
        $properties = [];
        $parts = $this->splitArrayShapeDefinition($shapeDefinition);

        foreach ($parts as $part) {
            if (preg_match('/^\s*([^:]+):\s*(.+)$/', $part, $matches)) {
                $key = trim($matches[1]);
                $type = trim($matches[2]);
                $tsType = $this->phpTypeToTypeScript($type);

                $properties[] = "{$key}: {$tsType}";
            }
        }

        return '{ ' . implode('; ', $properties) . ' }';
    }

    private function splitArrayShapeDefinition(string $definition): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($definition); $i++) {
            $char = $definition[$i];

            if ($char === '{' || $char === '[') {
                $depth++;
            } elseif ($char === '}' || $char === ']') {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private function phpTypeToTypeScript(string $phpType): string
    {
        $phpType = trim($phpType);

        // Basic type mappings (aligned with base TranspileTypeToTypeScriptAction)
        $typeMap = [
            // String types
            'string' => 'string',
            // Numeric types
            'int' => 'number',
            'integer' => 'number',
            'float' => 'number',
            'double' => 'number',
            // Boolean types
            'bool' => 'boolean',
            'boolean' => 'boolean',
            // Special types
            'array' => 'any[]',
            'object' => 'object',
            'mixed' => 'any',
            'null' => 'null',
            'void' => 'void',
            'scalar' => 'string|number|boolean',
        ];

        // Handle union types (string|int, int|null)
        if (str_contains($phpType, '|')) {
            $types = array_map('trim', explode('|', $phpType));
            $tsTypes = [];
            foreach ($types as $type) {
                // 括弧を削除
                $type = trim($type, '()');

                $tsTypes[] = $this->phpTypeToTypeScript($type);
            }
            return implode(' | ', $tsTypes);
        }

        // Handle nullable types
        if (str_starts_with($phpType, '?')) {
            $baseType = substr($phpType, 1);
            return $this->phpTypeToTypeScript($baseType) . ' | null';
        }

        // Handle array types
        if (str_ends_with($phpType, '[]')) {
            $baseType = substr($phpType, 0, -2);
            return $this->phpTypeToTypeScript($baseType) . '[]';
        }

        // Handle nested array shapes
        if (preg_match('/array\{([^}]+)\}/', $phpType, $matches)) {
            return $this->parseArrayShapeDefinition($matches[1]);
        }

        // Use type map if available
        if (isset($typeMap[$phpType])) {
            return $typeMap[$phpType];
        }

        // Convert class names to proper TypeScript namespace format
        if (class_exists($phpType) && str_starts_with($phpType, 'App\\')) {
            // App\Data\UserData -> App.Data.UserData
            return str_replace('\\', '.', $phpType);
        }

        // For class names, return as-is
        return $phpType;
    }

    private function convertTypeNodeToTypeScript(TypeNode $typeNode): ?string
    {
        if ($typeNode instanceof ArrayShapeNode) {
            $properties = [];

            foreach ($typeNode->items as $item) {
                if ($item instanceof ArrayShapeItemNode) {
                    $key = $item->keyName ? (string) $item->keyName : '';
                    $valueType = $this->convertTypeNodeToTypeScript($item->valueType);

                    if ($key && $valueType) {
                        // クォートを削除
                        $key = trim($key, '"\'');
                        $properties[] = "{$key}: {$valueType}";
                    }
                }
            }

            return '{ ' . implode('; ', $properties) . ' }';
        }

        if ($typeNode instanceof ArrayTypeNode) {
            $itemType = $this->convertTypeNodeToTypeScript($typeNode->type);
            return $itemType ? "{$itemType}[]" : 'any[]';
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->phpTypeToTypeScript($typeNode->name);
        }

        // その他の型ノードの場合は文字列として扱う
        return $this->phpTypeToTypeScript((string) $typeNode);
    }


    public function canTransform(ReflectionClass $class): bool
    {
        return $class->isSubclassOf(Data::class);
    }
}
