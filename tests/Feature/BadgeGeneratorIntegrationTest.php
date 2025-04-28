<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace Tests\Feature;

use BadgeGenerator\BadgeGeneratorFactory;
use BadgeGenerator\HttpClient\CurlHttpClient;
use BadgeGenerator\Services\ShieldsIoUrlBuilder;
use Mockery;
use Psr\Log\NullLogger;

beforeEach(function () {
    // Create a temporary directory for test badges
    if (!is_dir('test-badges')) {
        mkdir('test-badges', 0777, true);
    }
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir('test-badges')) {
        array_map('unlink', glob("test-badges/*.*"));
        rmdir('test-badges');
    }
    Mockery::close();
});

test('it integrates correctly with mocked dependencies', function () {
    $outputPath = 'test-badges/test.svg';
    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => $outputPath,
        'color' => 'green',
        'style' => 'flat-square'
    ];

    // Create a mock HTTP client that returns a fixed SVG
    $mockHttpClient = Mockery::mock(CurlHttpClient::class);
    $mockHttpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test badge</svg>');

    // Create a real URL builder to test the URL building logic
    $urlBuilder = new ShieldsIoUrlBuilder();

    // Create the generator with mocked dependencies
    $generator = new \BadgeGenerator\BadgeGenerator($urlBuilder, $mockHttpClient, $inputs);
    $path = $generator->generate();

    expect($path)->toBe($inputs['path']);
    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('<svg>test badge</svg>');
});

test('it handles all supported badge styles', function () {
    $styles = ['flat', 'flat-square', 'plastic', 'for-the-badge', 'social'];
    $mockHttpClient = Mockery::mock(CurlHttpClient::class);
    $urlBuilder = new ShieldsIoUrlBuilder();

    foreach ($styles as $style) {
        $outputPath = "test-badges/badge-{$style}.svg";
        $inputs = [
            'label' => 'style',
            'status' => $style,
            'path' => $outputPath,
            'style' => $style
        ];

        $mockHttpClient->shouldReceive('download')
            ->once()
            ->andReturn("<svg>badge with style {$style}</svg>");

        $generator = new \BadgeGenerator\BadgeGenerator($urlBuilder, $mockHttpClient, $inputs);
        $path = $generator->generate();

        expect($path)->toBe($inputs['path']);
        expect(file_exists($path))->toBeTrue();
        expect(file_get_contents($path))->toContain($style);
    }
});

test('it handles all supported color formats', function () {
    $colors = [
        'blue',                 // Named color
        'green',               // Another named color
        'red',                 // Another named color
        'brightgreen',         // Shields.io specific color
        'success',             // Semantic color
        'important'            // Semantic color
    ];

    foreach ($colors as $color) {
        $outputPath = "test-badges/test-{$color}.svg";
        $generator = BadgeGeneratorFactory::create([
            'label' => 'test',
            'status' => 'passing',
            'path' => $outputPath,
            'color' => $color
        ]);

        $path = $generator->generate();
        expect($path)->toBe($outputPath);
        expect(file_exists($path))->toBeTrue();
    }
});
