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
    if (!is_dir('var/tmp')) {
        mkdir('var/tmp', 0777, true);
    }
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir('var/tmp')) {
        array_map('unlink', glob("var/tmp/*.*"));
    }
    Mockery::close();
});

test('it integrates correctly with mocked dependencies', function () {
    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
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

    expect($path)->toBe('var/tmp/' . $inputs['path']);
    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('<svg>test badge</svg>');
});

test('it handles all supported badge styles', function () {
    $styles = ['flat', 'flat-square', 'plastic', 'for-the-badge', 'social'];
    $mockHttpClient = Mockery::mock(CurlHttpClient::class);
    $urlBuilder = new ShieldsIoUrlBuilder();

    foreach ($styles as $style) {
        $inputs = [
            'label' => 'style',
            'status' => $style,
            'path' => "badge-{$style}.svg",
            'style' => $style
        ];

        $mockHttpClient->shouldReceive('download')
            ->once()
            ->andReturn("<svg>badge with style {$style}</svg>");

        $generator = new \BadgeGenerator\BadgeGenerator($urlBuilder, $mockHttpClient, $inputs);
        $path = $generator->generate();

        expect($path)->toBe('var/tmp/' . $inputs['path']);
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
        $generator = BadgeGeneratorFactory::create([
            'label' => 'test',
            'status' => 'passing',
            'path' => "test-{$color}.svg",
            'color' => $color
        ]);

        $path = $generator->generate();
        expect($path)->toBe("var/tmp/test-{$color}.svg");
        expect(file_exists($path))->toBeTrue();
    }
});
