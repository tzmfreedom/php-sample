<?php

namespace App\PHPStan\Rules;

use App\PHPStan\TypedBladeTemplateParser;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Expr>
 */
class TypedViewDataRule implements Rule
{
    private TypedBladeTemplateParser $templateParser;

    public function __construct()
    {
        $this->templateParser = new TypedBladeTemplateParser();
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isTypedViewCall($node)) {
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
        
        return $this->validateDataTypes($expectedTypes, $providedData, $scope, $templateName);
    }

    private function isTypedViewCall(Node $node): bool
    {
        // TypedView::make()
        if ($node instanceof StaticCall) {
            return $this->isTypedViewStaticCall($node);
        }

        // app('typed-view')->make()
        if ($node instanceof MethodCall) {
            return $this->isTypedViewMethodCall($node, $node);
        }

        // typed_blade() or render_typed_blade()
        if ($node instanceof FuncCall) {
            return $this->isTypedViewFunctionCall($node);
        }

        return false;
    }

    private function isTypedViewStaticCall(StaticCall $node): bool
    {
        if (!$node->class instanceof Node\Name) {
            return false;
        }

        $className = $node->class->toString();
        $methodName = $node->name instanceof Node\Identifier ? $node->name->toString() : '';

        return ($className === 'TypedView' || $className === 'App\Facades\TypedView') && $methodName === 'make';
    }

    private function isTypedViewMethodCall(Node $node, MethodCall $methodCall): bool
    {
        if (!$methodCall->name instanceof Node\Identifier) {
            return false;
        }

        $methodName = $methodCall->name->toString();
        
        if ($methodName !== 'make') {
            return false;
        }

        // app('typed-view')形式をチェック
        if ($methodCall->var instanceof FuncCall &&
            $methodCall->var->name instanceof Node\Name &&
            $methodCall->var->name->toString() === 'app') {
            
            $args = $methodCall->var->args;
            if (count($args) > 0 && $args[0]->value instanceof Node\Scalar\String_) {
                return $args[0]->value->value === 'typed-view';
            }
        }

        return false;
    }

    private function isTypedViewFunctionCall(FuncCall $node): bool
    {
        if (!$node->name instanceof Node\Name) {
            return false;
        }

        $functionName = $node->name->toString();
        return in_array($functionName, ['typed_blade', 'render_typed_blade']);
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
                        'Template "%s" expects variable "$%s" of type "%s", but it was not provided.',
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
                        'Template "%s" expects variable "$%s" of type "%s", but "%s" provided.',
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
                        'Template "%s" does not expect variable "$%s", but it was provided.',
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