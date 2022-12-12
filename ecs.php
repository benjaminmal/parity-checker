<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $config): void {
    $config->import('vendor/benjaminmal/coding-standard/ecs.php');
    $config->paths([
        __DIR__ . '/src',
        __DIR__ . '/ecs.php',
    ]);
};
