<?php

namespace App;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class CustomMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === 'stdClass' && $methodName === 'getString';
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): \PHPStan\Reflection\MethodReflection
    {
        return new class implements MethodReflection
        {
            public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
            {
                $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

                return $reflectionProvider->getClass(\stdClass::class);
            }

            public function isStatic(): bool
            {
                return false;
            }

            public function isPrivate(): bool
            {
                return false;
            }

            public function isPublic(): bool
            {
                return true;
            }

            public function getDocComment(): ?string
            {
                return null;
            }

            public function getName(): string
            {
                return 'getString';
            }

            public function getPrototype(): \PHPStan\Reflection\ClassMemberReflection
            {
                return $this;
            }

            public function getVariants(): array
            {
                return [
                    new TrivialParametersAcceptor(),
                ];
            }

            public function isDeprecated(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): ?string
            {
                return null;
            }

            public function isFinal(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function isInternal(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getThrowType(): ?Type
            {
                return null;
            }

            public function hasSideEffects(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }
        };
    }
}

