<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withCache('/tmp/rector', FileCacheStorage::class)
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRules([
        \Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        \Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withPhp72Sets()
    ->withCodeQualityLevel(10)
    ->withDeadCodeLevel(10)
    ->withTypeCoverageLevel(10)
;
