<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace Tests;

use BadgeGenerator\BadgeGenerator;
use BadgeGenerator\BadgeGeneratorFactory;
use BadgeGenerator\HttpClient\CurlHttpClient;
use BadgeGenerator\Services\ShieldsIoUrlBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

beforeEach(function () {
    // Clean up any existing test directories
    if (is_dir('var/tmp')) {
        array_map('unlink', glob("var/tmp/*.*"));
    }
    if (!is_dir('var/tmp')) {
        mkdir('var/tmp', 0777, true);
    }
    chmod('var/tmp', 0777);
    clearstatcache();
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir('var/tmp')) {
        array_map('unlink', glob("var/tmp/*.*"));
    }
    clearstatcache();
});

test('factory creates badge generator with correct dependencies', function () {
    $generator = BadgeGeneratorFactory::create([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg'
    ]);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);

    // Test that the generator works
    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('factory creates badge generator with custom logger', function () {
    $logger = new NullLogger();
    $generator = BadgeGeneratorFactory::create([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg'
    ], $logger);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);

    // Test that the generator works
    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});
