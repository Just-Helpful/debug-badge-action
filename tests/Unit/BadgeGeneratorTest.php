<?php

namespace Tests\Unit;

use BadgeGenerator\BadgeGenerator;
use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Contracts\UrlBuilderInterface;
use BadgeGenerator\Exceptions\ValidationException;
use Mockery;
use org\bovigo\vfs\vfsStream;

test('it can be instantiated with required inputs', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => '/tmp/test.svg',
    ];

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);
    expect($generator)->toBeInstanceOf(BadgeGenerator::class);
});

test('it throws an exception when required inputs are missing', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        // Missing status and path
    ];

    expect(fn() => new BadgeGenerator($urlBuilder, $httpClient, $inputs))
        ->toThrow(ValidationException::class, 'Missing required input: status');
});

test('it sets default values for optional parameters', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => '/tmp/test.svg',
    ];

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);

    $reflection = new \ReflectionClass($generator);
    $paramsProperty = $reflection->getProperty('params');
    $paramsProperty->setAccessible(true);
    $params = $paramsProperty->getValue($generator);

    expect($params['style'])->toBe('flat');
    expect($params['label-color'])->toBe('555');
    expect($params['color'])->toBe('blue');
});

test('it correctly encodes URL parameters', function () {
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test label',
        'status' => 'test status',
        'path' => '/tmp/test.svg',
        'style' => 'flat-square',
        'label-color' => '000000',
        'color' => 'green',
    ];

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);

    $reflection = new \ReflectionClass($generator);
    $paramsProperty = $reflection->getProperty('params');
    $paramsProperty->setAccessible(true);
    $params = $paramsProperty->getValue($generator);

    expect($params['style'])->toBe('flat-square');
    expect($params['label-color'])->toBe('000000');
    expect($params['color'])->toBe('green');
});

test('it generates badge and saves to file', function () {
    $root = vfsStream::setup('root');
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => vfsStream::url('root/test.svg'),
    ];

    $expectedUrl = 'https://img.shields.io/badge/test-passing-blue';
    $expectedContent = '<svg>test badge content</svg>';

    $urlBuilder->shouldReceive('build')
        ->once()
        ->with($inputs['label'], $inputs['status'], Mockery::type('array'))
        ->andReturn($expectedUrl);

    $httpClient->shouldReceive('download')
        ->once()
        ->with($expectedUrl)
        ->andReturn($expectedContent);

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);
    $path = $generator->generate();

    expect($path)->toBe($inputs['path']);
    expect($root->hasChild('test.svg'))->toBeTrue();
    expect($root->getChild('test.svg')->getContent())->toBe($expectedContent);
});

test('it throws exception when directory creation fails', function () {
    $root = vfsStream::setup('root', 0444); // Read-only root
    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => vfsStream::url('root/subdir/test.svg'),
    ];

    $urlBuilder->shouldReceive('build')->andReturn('https://example.com/badge.svg');
    $httpClient->shouldReceive('download')->andReturn('<svg>content</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);
    expect(fn() => $generator->generate())->toThrow(\RuntimeException::class, 'Failed to create directory: ' . dirname($inputs['path']));
});

test('it throws exception when file saving fails', function () {
    $root = vfsStream::setup('root');
    $dir = vfsStream::newDirectory('badges', 0444); // Read-only directory
    $root->addChild($dir);

    $urlBuilder = Mockery::mock(UrlBuilderInterface::class);
    $httpClient = Mockery::mock(HttpClientInterface::class);

    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => vfsStream::url('root/badges/test.svg'),
    ];

    $urlBuilder->shouldReceive('build')->andReturn('https://example.com/badge.svg');
    $httpClient->shouldReceive('download')->andReturn('<svg>content</svg>');

    $generator = new BadgeGenerator($urlBuilder, $httpClient, $inputs);
    expect(fn() => $generator->generate())->toThrow(\RuntimeException::class, 'Failed to save badge to: ' . $inputs['path']);
});
