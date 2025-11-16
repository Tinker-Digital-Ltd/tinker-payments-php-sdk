<?php

declare(strict_types=1);

namespace Tools\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use Rector\Rector\AbstractRector;

final class ConvertNullableTypeToUnionTypeRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [
            Node\Param::class,
            Node\Stmt\ClassMethod::class,
            Node\Stmt\Function_::class,
            Node\Stmt\Property::class,
        ];
    }

    public function refactor(Node $node): Node|null
    {
        if ($node instanceof Node\Param) {
            return $this->refactorParam($node);
        }

        if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
            return $this->refactorFunction($node);
        }

        if ($node instanceof Node\Stmt\Property) {
            return $this->refactorProperty($node);
        }

        return null;
    }

    private function refactorParam(Node\Param $node): Node\Param|null
    {
        if (null === $node->type) {
            return null;
        }

        $newType = $this->convertNullableTypeToUnionType($node->type);
        if (null === $newType) {
            return null;
        }

        $node->type = $newType;

        return $node;
    }

    private function refactorFunction(Node $node): Node|null
    {
        if (null === $node->returnType) {
            return null;
        }

        $newType = $this->convertNullableTypeToUnionType($node->returnType);
        if (null === $newType) {
            return null;
        }

        $node->returnType = $newType;

        return $node;
    }

    private function refactorProperty(Node\Stmt\Property $node): Node\Stmt\Property|null
    {
        if (null === $node->type) {
            return null;
        }

        $newType = $this->convertNullableTypeToUnionType($node->type);
        if (null === $newType) {
            return null;
        }

        $node->type = $newType;

        return $node;
    }

    private function convertNullableTypeToUnionType(Node $type): Node|null
    {
        if (!$type instanceof NullableType) {
            return null;
        }

        $innerType = $type->type;
        if ($innerType instanceof UnionType) {
            $types = $innerType->types;
            $types[] = new Name('null');

            return new UnionType($types);
        }

        return new UnionType([$innerType, new Name('null')]);
    }
}
