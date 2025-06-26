<?php

namespace App\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\StringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\UnionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Generic\GenericObjectType;

class TypedBladeTemplateParser
{
    private array $parsedTemplates = [];

    public function parseTemplate(string $templatePath): array
    {
        if (isset($this->parsedTemplates[$templatePath])) {
            return $this->parsedTemplates[$templatePath];
        }

        if (!file_exists($templatePath)) {
            return [];
        }

        $content = file_get_contents($templatePath);
        $typeDeclarations = $this->extractTypeDeclarations($content);

        $this->parsedTemplates[$templatePath] = $typeDeclarations;

        return $typeDeclarations;
    }

    public function parseTemplateByName(string $templateName): array
    {
        $templatePath = $this->resolveTemplatePath($templateName);
        
        if (!$templatePath) {
            return [];
        }

        return $this->parseTemplate($templatePath);
    }

    private function extractTypeDeclarations(string $content): array
    {
        $typeDeclarations = [];
        
        // @var Type $variable パターンをマッチ
        preg_match_all('/@var\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $typeString = $match[1];
            $variableName = $match[2];
            
            $type = $this->parseTypeString($typeString);
            $typeDeclarations[$variableName] = [
                'type' => $type,
                'typeString' => $typeString,
                'nullable' => $this->isNullableType($typeString),
            ];
        }

        return $typeDeclarations;
    }

    private function parseTypeString(string $typeString): Type
    {
        $typeString = trim($typeString);

        // Nullable型 (?Type または Type|null)
        if (str_starts_with($typeString, '?')) {
            $baseType = $this->parseTypeString(substr($typeString, 1));
            return new UnionType([$baseType, new NullType()]);
        }

        if (str_contains($typeString, '|null')) {
            $baseTypeString = str_replace('|null', '', $typeString);
            $baseType = $this->parseTypeString($baseTypeString);
            return new UnionType([$baseType, new NullType()]);
        }

        if (str_contains($typeString, 'null|')) {
            $baseTypeString = str_replace('null|', '', $typeString);
            $baseType = $this->parseTypeString($baseTypeString);
            return new UnionType([new NullType(), $baseType]);
        }

        // Union型 (Type1|Type2)
        if (str_contains($typeString, '|')) {
            $types = explode('|', $typeString);
            $unionTypes = array_map(fn($t) => $this->parseTypeString(trim($t)), $types);
            return new UnionType($unionTypes);
        }

        // 配列型 (Type[])
        if (str_ends_with($typeString, '[]')) {
            $itemTypeString = substr($typeString, 0, -2);
            $itemType = $this->parseTypeString($itemTypeString);
            return new ArrayType(new MixedType(), $itemType);
        }

        // Generic型 (Collection<Type>)
        if (preg_match('/^([^<]+)<([^>]+)>$/', $typeString, $matches)) {
            $containerClass = $matches[1];
            $itemTypeString = $matches[2];
            $itemType = $this->parseTypeString($itemTypeString);
            
            return new GenericObjectType($containerClass, [$itemType]);
        }

        // プリミティブ型
        return match ($typeString) {
            'string' => new StringType(),
            'int', 'integer' => new IntegerType(),
            'float', 'double' => new FloatType(),
            'bool', 'boolean' => new BooleanType(),
            'array' => new ArrayType(new MixedType(), new MixedType()),
            'object' => new ObjectType('stdClass'),
            'null' => new NullType(),
            'mixed' => new MixedType(),
            default => new ObjectType($typeString), // クラス型
        };
    }

    private function isNullableType(string $typeString): bool
    {
        return str_starts_with($typeString, '?') ||
               str_contains($typeString, '|null') ||
               str_contains($typeString, 'null|');
    }

    private function resolveTemplatePath(string $templateName): ?string
    {
        $basePaths = [
            resource_path('views/typed'),
        ];

        $templateFile = str_replace('.', '/', $templateName) . '.tblade.php';

        foreach ($basePaths as $basePath) {
            $fullPath = $basePath . '/' . $templateFile;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    public function getExpectedDataTypes(string $templateName): array
    {
        $typeDeclarations = $this->parseTemplateByName($templateName);
        $expectedTypes = [];

        foreach ($typeDeclarations as $variableName => $declaration) {
            $expectedTypes[$variableName] = $declaration['type'];
        }

        return $expectedTypes;
    }

    public function clearCache(): void
    {
        $this->parsedTemplates = [];
    }
}