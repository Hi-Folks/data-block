<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(
        php81: true,
    )
    ->withPreparedSets(
        //deadCode: true,
        //codeQuality: true,
        earlyReturn: true,
        // typeDeclarations: true,
        privatization: true,
    )
    ->withTypeCoverageLevel(10)
    ->withCodeQualityLevel(10)
    ->withDeadCodeLevel(10);
