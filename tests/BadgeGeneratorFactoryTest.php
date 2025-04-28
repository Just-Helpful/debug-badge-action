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
    // Create a temporary directory for test badges
    if (!is_dir('test-badges')) {
        mkdir('test-badges', 0777, true);
    }
    chmod('test-badges', 0777);
    clearstatcache();
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir('test-badges')) {
        array_map('unlink', glob("test-badges/*.*"));
        rmdir('test-badges');
    }
    clearstatcache();
});

test('factory creates badge generator with correct dependencies', function () {
    $outputPath = 'test-badges/test.svg';
    $generator = BadgeGeneratorFactory::create([
        'label' => 'test',
        'status' => 'passing',
        'path' => $outputPath
    ]);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);

    // Test that the generator works
    $path = $generator->generate();
    expect($path)->toBe($outputPath);
    expect(file_exists($path))->toBeTrue();
});

test('factory creates badge generator with custom logger', function () {
    $logger = new NullLogger();
    $outputPath = 'test-badges/test-logger.svg';
    $generator = BadgeGeneratorFactory::create([
        'label' => 'test',
        'status' => 'passing',
        'path' => $outputPath
    ], $logger);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);

    // Test that the generator works
    $path = $generator->generate();
    expect($path)->toBe($outputPath);
    expect(file_exists($path))->toBeTrue();
});
