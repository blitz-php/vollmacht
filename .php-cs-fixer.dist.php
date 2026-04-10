<?php

/**
 * This file is part of Dimtrovich - Console.
 *
 * (c) 2026 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\CodingStandard\Blitz;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([__DIR__ . '/src', __DIR__ . '/spec'])
    ->append([__FILE__]);

$overrides = [
    'void_return' => true,
];

$options = [
    'cacheFile' => 'build/.php-cs-fixer.cache',
    'finder'    => $finder,
];

return Factory::create(new Blitz(), $overrides, $options)->forLibrary(
    'Blitz PHP framework - Schild',
    'Dimitri Sitchet Tomkeu',
    'devcode.dst@gmail.com',
    2026,
);
