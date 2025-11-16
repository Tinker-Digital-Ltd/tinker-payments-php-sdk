<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
readonly class ExceptionMustHaveMessageAndCodeRule implements Rule
{
    private const string IDENTIFIER = 'exception.must.have.message.and.code';

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $namespace = $scope->getNamespace();
        if (null === $namespace || !str_starts_with($namespace, 'App\\Exception')) {
            return [];
        }

        // Skip abstract classes
        if (($node->flags & Class_::MODIFIER_ABSTRACT) !== 0) {
            return [];
        }

        // Must extend something (should be an exception class)
        if (null === $node->extends) {
            return [];
        }

        $className = $node->name?->toString() ?? 'Unknown';

        // Check if it's an empty exception class
        if ($this->isEmptyExceptionClass($node)) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Exception class %s is empty and must provide a constructor with code parameter and non-empty message in parent constructor call.',
                        $className,
                    ),
                )
                    ->identifier(self::IDENTIFIER)
                    ->build(),
            ];
        }

        // Check if it has a constructor
        $constructor = $this->findConstructor($node);
        if (!$constructor) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Exception class %s must have a constructor with code parameter and non-empty message in parent constructor call.',
                        $className,
                    ),
                )
                    ->identifier(self::IDENTIFIER)
                    ->build(),
            ];
        }

        // Validate constructor
        $validationError = $this->validateConstructor($constructor, $className);
        if ($validationError) {
            return [$validationError];
        }

        return [];
    }

    private function isEmptyExceptionClass(Class_ $node): bool
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod || $stmt instanceof Property) {
                return false;
            }
        }

        return true;
    }

    private function findConstructor(Class_ $node): ClassMethod|null
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && '__construct' === $stmt->name->toString()) {
                return $stmt;
            }
        }

        return null;
    }

    private function validateConstructor(ClassMethod $constructor, string $className): IdentifierRuleError|null
    {
        // Constructor must have 'code' parameter
        $codeParam = $this->findParameterByName($constructor->params);
        if (!$codeParam) {
            return RuleErrorBuilder::message(
                sprintf(
                    'Exception class %s constructor must have a "code" (int) parameter.',
                    $className,
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build();
        }

        // Check code parameter type
        if (!$this->isIntType($codeParam)) {
            return RuleErrorBuilder::message(
                sprintf(
                    'Exception class %s constructor "code" parameter must be of type int.',
                    $className,
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build();
        }

        // Constructor must call parent::__construct with non-empty message
        if (!$this->hasValidParentConstructorCall($constructor)) {
            return RuleErrorBuilder::message(
                sprintf(
                    'Exception class %s constructor must call parent::__construct() with a non-empty message parameter.',
                    $className,
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build();
        }

        return null;
    }

    private function hasValidParentConstructorCall(ClassMethod $constructor): bool
    {
        if (!$constructor->stmts) {
            return false;
        }

        foreach ($constructor->stmts as $stmt) {
            $parentCall = $this->findParentConstructorCall($stmt);
            if ($parentCall && $this->hasNonEmptyMessageArgument($parentCall)) {
                return true;
            }
        }

        return false;
    }

    private function findParentConstructorCall(Node $stmt): StaticCall|null
    {
        if (!($stmt instanceof Expression) || !($stmt->expr instanceof StaticCall)) {
            return null;
        }

        $staticCall = $stmt->expr;

        if (!$this->isParentConstructorCall($staticCall)) {
            return null;
        }

        return $staticCall;
    }

    private function isParentConstructorCall(StaticCall $staticCall): bool
    {
        return $staticCall->class instanceof Node\Name
               && 'parent' === $staticCall->class->toString()
               && $staticCall->name instanceof Node\Identifier
               && '__construct' === $staticCall->name->toString();
    }

    private function hasNonEmptyMessageArgument(StaticCall $staticCall): bool
    {
        if (empty($staticCall->args)) {
            return false;
        }

        $firstArg = $staticCall->args[0];

        // Skip variadic placeholders
        if ($firstArg instanceof VariadicPlaceholder) {
            return false;
        }

        // If it's a string literal, check if it's not empty
        if ($firstArg->value instanceof Node\Scalar\String_) {
            return !empty($firstArg->value->value);
        }

        // If it's a variable or expression (like sprintf), assume it's valid
        // We can't easily check the runtime value, so we trust it's not empty
        return true;
    }

    /**
     * @param array<Param> $params
     */
    private function findParameterByName(array $params): Param|null
    {
        foreach ($params as $param) {
            if (!$param instanceof Param) {
                continue;
            }

            $paramName = $param->var->name ?? '';
            if ('code' === $paramName) {
                return $param;
            }
        }

        return null;
    }

    private function isIntType(Param $param): bool
    {
        if (!$param->type) {
            return false;
        }

        return $param->type instanceof Node\Identifier && 'int' === $param->type->toString();
    }
}
