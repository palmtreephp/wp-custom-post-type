<?php

declare(strict_types=1);

use Palmtree\PhpCsFixerConfig\Config;

$config = new Config();

$config
    ->setRules(array_merge($config->getRules(), [
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],
    ]))
    ->getFinder()
    ->in(__DIR__ . '/src')
    ->append([__FILE__])
;

return $config;
