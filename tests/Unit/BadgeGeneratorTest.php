<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace Tests\Unit;

use BadgeGenerator\BadgeGenerator;
use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Contracts\UrlBuilderInterface;
use BadgeGenerator\Exceptions\ValidationException;
use Mockery;
use Psr\Log\NullLogger;
use phpmock\phpunit\PHPMock;

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
    Mockery::close();
});

test('it can be instantiated with required inputs', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'var/tmp/test.svg'
    ]);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);
});

test('it throws an exception when required inputs are missing', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    expect(fn() => new BadgeGenerator($urlBuilder, $httpClient, []))
        ->toThrow(ValidationException::class, 'Missing required input: label');
});

test('it sets default values for optional parameters', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with('test', 'passing', [
            'style' => 'flat',
            'label-color' => '555',
            'color' => 'blue'
        ])
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('it correctly encodes URL parameters', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with('test/path', 'status/value', Mockery::any())
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test/path',
        'status' => 'status/value',
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('it generates badge and saves to file', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('<svg>test</svg>');
});

test('it throws exception when directory cannot be created', function () {
    set_error_handler(fn() => true); // Suppress warnings
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->andReturn('https://example.com/badge.svg');
    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    // Create a read-only parent directory
    $parentDir = 'var/tmp/readonly-parent';
    $childDir = $parentDir . '/child';
    if (!is_dir($parentDir)) {
        mkdir($parentDir, 0777, true);
    }
    chmod($parentDir, 0555); // read and execute only, no write

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => $childDir . '/test.svg'
    ]);

    expect(fn() => $generator->generate())
        ->toThrow(\Exception::class, 'Failed to save badge: Could not create directory');

    // Cleanup
    chmod($parentDir, 0777);
    if (is_dir($childDir)) {
        rmdir($childDir);
    }
    rmdir($parentDir);
    restore_error_handler();
});

test('it throws exception when file saving fails', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    // Create a read-only directory
    $testDir = 'var/tmp/readonly';
    if (!is_dir($testDir)) {
        mkdir($testDir, 0777, true);
    }
    chmod($testDir, 0444);

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => $testDir . '/test.svg'
    ]);

    expect(fn() => $generator->generate())
        ->toThrow(\Exception::class, 'Failed to save badge: Directory is not writable');

    // Cleanup
    chmod($testDir, 0777);
    rmdir($testDir);
});

test('it handles special characters in label and status', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with('test/with/slashes', 'passing with spaces', Mockery::any())
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test/with/slashes',
        'status' => 'passing with spaces',
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('it handles unicode characters in label and status', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with('测试', '通过', Mockery::any())
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => '测试',
        'status' => '通过',
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('it handles very long label and status values', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $longText = str_repeat('a', 100);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with($longText, $longText, Mockery::any())
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->once()
        ->andReturn('<svg>test</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => $longText,
        'status' => $longText,
        'path' => 'var/tmp/test.svg'
    ]);

    $path = $generator->generate();
    expect($path)->toBe('var/tmp/test.svg');
    expect(file_exists($path))->toBeTrue();
});

test('it handles file overwriting correctly', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->twice()
        ->andReturn('https://example.com/badge.svg');

    $httpClient->shouldReceive('download')
        ->twice()
        ->andReturn('<svg>test1</svg>', '<svg>test2</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'var/tmp/test.svg'
    ]);

    // Generate first badge
    $path = $generator->generate();
    expect(file_get_contents($path))->toBe('<svg>test1</svg>');

    // Generate second badge with same path
    $path = $generator->generate();
    expect(file_get_contents($path))->toBe('<svg>test2</svg>');
});

test('it handles concurrent file access', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->times(2)
        ->andReturn('https://example.com/badge1.svg', 'https://example.com/badge2.svg');

    $httpClient->shouldReceive('download')
        ->times(2)
        ->andReturn('<svg>test1</svg>', '<svg>test2</svg>');

    // Create two generators with different paths
    $generator1 = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test1',
        'status' => 'passing',
        'path' => 'var/tmp/test1.svg'
    ]);

    $generator2 = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test2',
        'status' => 'passing',
        'path' => 'var/tmp/test2.svg'
    ]);

    // Generate badges "concurrently"
    $path1 = $generator1->generate();
    $path2 = $generator2->generate();

    expect(file_get_contents($path1))->toBe('<svg>test1</svg>');
    expect(file_get_contents($path2))->toBe('<svg>test2</svg>');
});

test('it throws an exception if httpClient download fails', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $urlBuilder->shouldReceive('build')
        ->once()
        ->andReturn('https://example.com/badge.svg');
    $httpClient->shouldReceive('download')
        ->once()
        ->andThrow(new \Exception('Network error'));

    $generator = new BadgeGenerator($urlBuilder, $httpClient, [
        'label' => 'test',
        'status' => 'passing',
        'path' => 'var/tmp/test.svg'
    ]);

    expect(fn() => $generator->generate())
        ->toThrow(\Exception::class, 'Network error');
});
