<?php

namespace App\PHPStan;

use PHPStan\Type\Type;
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

class BladeTemplateParser
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
        
        // @php ... @endphp ブロックを抽出
        preg_match_all('/@php\s*(.*?)\s*@endphp/s', $content, $phpBlocks, PREG_SET_ORDER);
        
        foreach ($phpBlocks as $phpBlock) {
            $phpContent = $phpBlock[1];
            
            // /** @var Type $variable */ パターンを抽出
            preg_match_all('/\/\*\*\s*@var\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\*\//', $phpContent, $matches, PREG_SET_ORDER);
            
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
        }

        // 単行での /** @var Type $variable */ パターンも抽出
        preg_match_all('/\/\*\*\s*@var\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\*\//', $content, $singleLineMatches, PREG_SET_ORDER);
        
        foreach ($singleLineMatches as $match) {
            $typeString = $match[1];
            $variableName = $match[2];
            
            // @php ブロック内で既に処理済みでない場合のみ追加
            if (!isset($typeDeclarations[$variableName])) {
                $type = $this->parseTypeString($typeString);
                $typeDeclarations[$variableName] = [
                    'type' => $type,
                    'typeString' => $typeString,
                    'nullable' => $this->isNullableType($typeString),
                ];
            }
        }

        return $typeDeclarations;
    }

    private function parseTypeString(string $typeString): Type
    {
        $typeString = trim($typeString);

        // 完全なクラス名の正規化（先頭の \ を削除）
        $typeString = ltrim($typeString, '\\');

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
        $primitiveTypes = [
            'string' => new StringType(),
            'int' => new IntegerType(),
            'integer' => new IntegerType(),
            'float' => new FloatType(),
            'double' => new FloatType(),
            'bool' => new BooleanType(),
            'boolean' => new BooleanType(),
            'array' => new ArrayType(new MixedType(), new MixedType()),
            'object' => new ObjectType('stdClass'),
            'null' => new NullType(),
            'mixed' => new MixedType(),
        ];

        if (isset($primitiveTypes[$typeString])) {
            return $primitiveTypes[$typeString];
        }

        // Laravelの短縮形を完全なクラス名に変換
        $typeAliases = [
            'Collection' => 'Illuminate\\Support\\Collection',
            'Model' => 'Illuminate\\Database\\Eloquent\\Model',
            'Request' => 'Illuminate\\Http\\Request',
            'User' => 'App\\Models\\User',
        ];

        if (isset($typeAliases[$typeString])) {
            $typeString = $typeAliases[$typeString];
        }

        // クラス型
        return new ObjectType($typeString);
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
            resource_path('views'),
        ];

        $templateFile = str_replace('.', '/', $templateName) . '.blade.php';

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

    /**
     * デバッグ用: テンプレートの型宣言を表示
     */
    public function debugTemplate(string $templateName): array
    {
        $typeDeclarations = $this->parseTemplateByName($templateName);
        $debug = [];

        foreach ($typeDeclarations as $variableName => $declaration) {
            $debug[$variableName] = [
                'typeString' => $declaration['typeString'],
                'phpStanType' => $declaration['type']->describe(\PHPStan\Type\VerbosityLevel::typeOnly()),
                'nullable' => $declaration['nullable'],
            ];
        }

        return $debug;
    }
}