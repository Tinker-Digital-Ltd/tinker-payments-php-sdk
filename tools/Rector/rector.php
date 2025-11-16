<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php74\Rector\Assign\NullCoalescingOperatorRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Tools\Rector\Rules\ConvertNullableTypeToUnionTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->cacheDirectory(__DIR__.'/../../var/cache/rector');

    $rectorConfig->importShortClasses();

    $rectorConfig->paths([
        __DIR__.'/../../src',
        __DIR__.'/../../tests',
        __DIR__.'/../../tools',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,

        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,

        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->skip([RecastingRemovalRector::class]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        NullCoalescingOperatorRector::class,
        ChangeSwitchToMatchRector::class,
        ReadOnlyPropertyRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnUnionTypeRector::class,
        ConvertNullableTypeToUnionTypeRector::class,
    ]);
};
