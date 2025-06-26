<?php

namespace App\PHPStan;

use PHPStan\Type\Type;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;

class TypedBladeHelper
{
    private static array $typeAliases = [
        'User' => 'App\Models\User',
        'Collection' => 'Illuminate\Support\Collection',
        'Model' => 'Illuminate\Database\Eloquent\Model',
        'Request' => 'Illuminate\Http\Request',
    ];

    public static function resolveTypeAlias(string $typeName): string
    {
        return self::$typeAliases[$typeName] ?? $typeName;
    }

    public static function setTypeAliases(array $aliases): void
    {
        self::$typeAliases = array_merge(self::$typeAliases, $aliases);
    }

    public static function isAssignableType(Type $expected, Type $actual): bool
    {
        return $expected->accepts($actual, true)->yes();
    }

    public static function formatTypeForError(Type $type): string
    {
        return $type->describe(\PHPStan\Type\VerbosityLevel::typeOnly());
    }

    public static function isControllerClass(string $className): bool
    {
        return str_ends_with($className, 'Controller') ||
               str_contains($className, '\\Controllers\\') ||
               str_contains($className, 'App\\Http\\Controllers\\');
    }

    public static function normalizeTemplateName(string $templateName): string
    {
        // ドット記法をスラッシュ記法に変換
        return str_replace('.', '/', $templateName);
    }

    public static function findTemplateFile(string $templateName): ?string
    {
        $basePaths = [
            resource_path('views/typed'),
        ];

        $possibleNames = [
            self::normalizeTemplateName($templateName) . '.tblade.php',
            $templateName . '.tblade.php',
        ];

        foreach ($basePaths as $basePath) {
            foreach ($possibleNames as $fileName) {
                $fullPath = $basePath . '/' . $fileName;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
        }

        return null;
    }

    public static function extractErrorContext(string $templateName, string $variableName): array
    {
        $templatePath = self::findTemplateFile($templateName);
        
        if (!$templatePath) {
            return [];
        }

        $content = file_get_contents($templatePath);
        $lines = explode("\n", $content);
        
        // @var 宣言の行を見つける
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/@var\s+[^\s]+\s+\$' . preg_quote($variableName, '/') . '/', $line)) {
                return [
                    'file' => $templatePath,
                    'line' => $lineNumber + 1,
                    'declaration' => trim($line),
                ];
            }
        }

        return [];
    }

    public static function isNullableTypeAccepted(Type $expectedType, Type $actualType): bool
    {
        // NULL値が期待される型に受け入れられるかチェック
        if ($actualType instanceof \PHPStan\Type\NullType) {
            return $expectedType instanceof \PHPStan\Type\UnionType &&
                   $expectedType->accepts($actualType, true)->yes();
        }

        return false;
    }

    public static function getSuggestedFix(string $expectedTypeName, string $actualTypeName): ?string
    {
        $suggestions = [
            'string->int' => 'Cast to integer: (int) $value',
            'string->bool' => 'Cast to boolean: (bool) $value',
            'array->Collection' => 'Convert to Collection: collect($value)',
            'stdClass->User' => 'Fetch User model: User::find($id)',
        ];

        $key = $actualTypeName . '->' . $expectedTypeName;
        return $suggestions[$key] ?? null;
    }
}