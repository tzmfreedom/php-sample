<?php

namespace App\PHPStan\Rules;

use App\PHPStan\TypedBladeTemplateParser;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Rule<Return_>
 */
class TypedViewControllerDataRule implements Rule
{
    private TypedBladeTemplateParser $templateParser;
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->templateParser = new TypedBladeTemplateParser();
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->expr) {
            return [];
        }

        // コントローラーメソッド内でのみチェック
        if (!$this->isInControllerMethod($scope)) {
            return [];
        }

        if (!$this->isTypedViewCall($node->expr)) {
            return [];
        }

        $templateName = $this->extractTemplateName($node->expr, $scope);
        if (!$templateName) {
            return [];
        }

        $providedData = $this->analyzeProvidedData($node->expr, $scope);
        if ($providedData === null) {
            return [];
        }

        $expectedTypes = $this->templateParser->getExpectedDataTypes($templateName);
        
        return $this->validateControllerData($expectedTypes, $providedData, $scope, $templateName, $node);
    }

    private function isInControllerMethod(Scope $scope): bool
    {
        $function = $scope->getFunction();
        if (!$function) {
            return false;
        }

        $declaringClass = $function->getDeclaringClass();
        if (!$declaringClass) {
            return false;
        }

        $className = $declaringClass->getName();
        
        // Controllerクラスかどうかをチェック
        return str_ends_with($className, 'Controller') || 
               $this->isSubclassOfController($className);
    }

    private function isSubclassOfController(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        
        // Laravel Controllerの基底クラスをチェック
        $baseControllerClasses = [
            'Illuminate\Routing\Controller',
            'App\Http\Controllers\Controller',
        ];

        foreach ($baseControllerClasses as $baseClass) {
            if ($this->reflectionProvider->hasClass($baseClass) && 
                $classReflection->isSubclassOf($baseClass)) {
                return true;
            }
        }

        return false;
    }

    private function isTypedViewCall(Node $node): bool
    {
        // TypedView::make()
        if ($node instanceof StaticCall) {
            return $this->isTypedViewStaticCall($node);
        }

        // app('typed-view')->make()
        if ($node instanceof MethodCall) {
            return $this->isTypedViewMethodCall($node);
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

    private function isTypedViewMethodCall(MethodCall $node): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $methodName = $node->name->toString();
        
        if ($methodName !== 'make') {
            return false;
        }

        // app('typed-view')形式をチェック
        if ($node->var instanceof FuncCall &&
            $node->var->name instanceof Node\Name &&
            $node->var->name->toString() === 'app') {
            
            $args = $node->var->args;
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

    private function analyzeProvidedData(Node $node, Scope $scope): ?array
    {
        $args = $this->getCallArguments($node);
        
        if (count($args) < 2) {
            return [];
        }

        $dataArg = $args[1];
        
        // 配列リテラルの詳細解析
        if ($dataArg->value instanceof Array_) {
            return $this->analyzeArrayLiteral($dataArg->value, $scope);
        }

        // 変数の場合は基本的な型情報のみ
        if ($dataArg->value instanceof Variable) {
            return $this->analyzeVariable($dataArg->value, $scope);
        }

        return null;
    }

    private function analyzeArrayLiteral(Array_ $arrayNode, Scope $scope): array
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
                
                $data[$key] = [
                    'type' => $valueType,
                    'node' => $item->value,
                    'definite' => true,
                ];
            }
        }

        return $data;
    }

    private function analyzeVariable(Variable $variableNode, Scope $scope): array
    {
        $variableType = $scope->getType($variableNode);
        
        // 配列型の場合、基本的な情報のみ提供
        return [
            '_variable' => [
                'type' => $variableType,
                'node' => $variableNode,
                'definite' => false,
            ]
        ];
    }

    private function getCallArguments(Node $node): array
    {
        if ($node instanceof StaticCall || $node instanceof MethodCall || $node instanceof FuncCall) {
            return $node->args;
        }

        return [];
    }

    private function validateControllerData(array $expectedTypes, array $providedData, Scope $scope, string $templateName, Node $node): array
    {
        $errors = [];

        // 変数データの場合は簡単なチェックのみ
        if (isset($providedData['_variable'])) {
            return []; // 変数データの詳細チェックは困難なのでスキップ
        }

        // 配列リテラルの詳細チェック
        foreach ($expectedTypes as $variableName => $expectedType) {
            if (!array_key_exists($variableName, $providedData)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Controller method returns TypedView for template "%s" but missing required variable "$%s" of type "%s".',
                        $templateName,
                        $variableName,
                        $expectedType->describe(VerbosityLevel::typeOnly())
                    )
                )->line($node->getLine())
                ->build();
                continue;
            }

            $providedInfo = $providedData[$variableName];
            $providedType = $providedInfo['type'];
            
            if (!$expectedType->accepts($providedType, true)->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Controller method provides variable "$%s" of type "%s" to template "%s", but template expects "%s".',
                        $variableName,
                        $providedType->describe(VerbosityLevel::typeOnly()),
                        $templateName,
                        $expectedType->describe(VerbosityLevel::typeOnly())
                    )
                )->line($providedInfo['node']->getLine())
                ->build();
            }
        }

        // 未使用変数の警告
        foreach ($providedData as $variableName => $providedInfo) {
            if (!array_key_exists($variableName, $expectedTypes)) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Controller method provides unused variable "$%s" to template "%s".',
                        $variableName,
                        $templateName
                    )
                )->line($providedInfo['node']->getLine())
                ->tip('Consider removing this variable or adding it to the template.')
                ->build();
            }
        }

        return $errors;
    }
}