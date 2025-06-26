<?php

namespace App\PHPStan\Rules;

use App\PHPStan\BladeTemplateParser;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Expr>
 */
class BladeViewDataRule implements Rule
{
    private BladeTemplateParser $templateParser;

    public function __construct()
    {
        $this->templateParser = new BladeTemplateParser();
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isViewCall($node)) {
            return [];
        }

        $templateName = $this->extractTemplateName($node, $scope);
        if (!$templateName) {
            return [];
        }

        $providedData = $this->extractProvidedData($node, $scope);
        if ($providedData === null) {
            return [];
        }

        $expectedTypes = $this->templateParser->getExpectedDataTypes($templateName);
        
        // 型宣言がない場合はスキップ
        if (empty($expectedTypes)) {
            return [];
        }
        
        return $this->validateDataTypes($expectedTypes, $providedData, $scope, $templateName);
    }

    private function isViewCall(Node $node): bool
    {
        // view()
        if ($node instanceof FuncCall) {
            return $this->isViewFunctionCall($node);
        }

        // View::make()
        if ($node instanceof StaticCall) {
            return $this->isViewStaticCall($node);
        }

        // app('view')->make()
        if ($node instanceof MethodCall) {
            return $this->isViewMethodCall($node);
        }

        return false;
    }

    private function isViewFunctionCall(FuncCall $node): bool
    {
        if (!$node->name instanceof Node\Name) {
            return false;
        }

        $functionName = $node->name->toString();
        return $functionName === 'view';
    }

    private function isViewStaticCall(StaticCall $node): bool
    {
        if (!$node->class instanceof Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '';

        return ($className === 'View' || $className === 'Illuminate\Support\Facades\View') && $methodName === 'make';
    }

    private function isViewMethodCall(MethodCall $node): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $methodName = $node->name->toString();
        
        if ($methodName !== 'make') {
            return false;
        }

        // app('view')形式をチェック
        if ($node->var instanceof FuncCall &&
            $node->var->name instanceof Node\Name &&
            $node->var->name->toString() === 'app') {
            
            $args = $node->var->args;
            if (count($args) > 0 && $args[0]->value instanceof Node\Scalar\String_) {
                return $args[0]->value->value === 'view';
            }
        }

        return false;
    }

    private function extractTemplateName(Node $node, Scope $scope): ?string
    {
        $args = $this->getCallArguments($node);
        
        if (empty($args)) {
            return null;
        }

        $templateArg = $args[0];
        $templateType = $scope->getType($templateArg->value);

        if ($templateType instanceof ConstantStringType) {
            return $templateType->getValue();
        }

        return null;
    }

    private function extractProvidedData(Node $node, Scope $scope): ?array
    {
        $args = $this->getCallArguments($node);
        
        if (count($args) < 2) {
            return [];
        }

        $dataArg = $args[1];
        $dataType = $scope->getType($dataArg->value);

        // 配列リテラルの場合
        if ($dataArg->value instanceof Node\Expr\Array_) {
            return $this->extractArrayData($dataArg->value, $scope);
        }

        return null;
    }

    private function extractArrayData(Node\Expr\Array_ $arrayNode, Scope $scope): array
    {
        $data = [];

        foreach ($arrayNode->items as $item) {
            if ($item === null || $item->key === null) {
                continue;
            }

            $keyType = $scope->getType($item->key);
            if ($keyType instanceof ConstantStringType) {
                $key = $keyType->getValue();
                $valueType = $scope->getType($item->value);
                $data[$key] = $valueType;
            }
        }

        return $data;
    }

    private function getCallArguments(Node $node): array
    {
        if ($node instanceof StaticCall || $node instanceof MethodCall || $node instanceof FuncCall) {
            return $node->args;
        }

        return [];
    }

    private function validateDataTypes(array $expectedTypes, array $providedData, Scope $scope, string $templateName): array
    {
        $errors = [];

        // 必須フィールドの不足をチェック
        foreach ($expectedTypes as $variableName => $expectedType) {
            if (!array_key_exists($variableName, $providedData)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Blade template "%s" expects variable "$%s" of type "%s", but it was not provided.',
                        $templateName,
                        $variableName,
                        $expectedType->describe(VerbosityLevel::typeOnly())
                    )
                )->build();
                continue;
            }

            $providedType = $providedData[$variableName];
            
            if (!$expectedType->accepts($providedType, true)->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Blade template "%s" expects variable "$%s" of type "%s", but "%s" provided.',
                        $templateName,
                        $variableName,
                        $expectedType->describe(VerbosityLevel::typeOnly()),
                        $providedType->describe(VerbosityLevel::typeOnly())
                    )
                )->build();
            }
        }

        // 余分なデータの警告
        foreach ($providedData as $variableName => $providedType) {
            if (!array_key_exists($variableName, $expectedTypes)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Blade template "%s" does not expect variable "$%s", but it was provided.',
                        $templateName,
                        $variableName
                    )
                )->tip('This variable will be ignored by the template.')
                ->build();
            }
        }

        return $errors;
    }
}