<?php

namespace Tests;

use BadgeGenerator\BadgeGenerator;
use BadgeGenerator\BadgeGeneratorFactory;
use BadgeGenerator\HttpClient\CurlHttpClient;
use BadgeGenerator\Services\ShieldsIoUrlBuilder;

test('factory creates badge generator with correct dependencies', function () {
    $inputs = [
        'label' => 'test',
        'status' => 'passing',
        'path' => '/tmp/test.svg',
        'color' => 'green',
    ];

    $generator = BadgeGeneratorFactory::create($inputs);

    expect($generator)->toBeInstanceOf(BadgeGenerator::class);

    // Test that the generator has the correct dependencies using reflection
    $reflection = new \ReflectionClass($generator);

    $urlBuilderProperty = $reflection->getProperty('urlBuilder');
    $urlBuilderProperty->setAccessible(true);
    expect($urlBuilderProperty->getValue($generator))->toBeInstanceOf(ShieldsIoUrlBuilder::class);

    $httpClientProperty = $reflection->getProperty('httpClient');
    $httpClientProperty->setAccessible(true);
    expect($httpClientProperty->getValue($generator))->toBeInstanceOf(CurlHttpClient::class);

    // Check individual properties
    $labelProperty = $reflection->getProperty('label');
    $labelProperty->setAccessible(true);
    expect($labelProperty->getValue($generator))->toBe($inputs['label']);

    $statusProperty = $reflection->getProperty('status');
    $statusProperty->setAccessible(true);
    expect($statusProperty->getValue($generator))->toBe($inputs['status']);

    $pathProperty = $reflection->getProperty('path');
    $pathProperty->setAccessible(true);
    expect($pathProperty->getValue($generator))->toBe($inputs['path']);

    $paramsProperty = $reflection->getProperty('params');
    $paramsProperty->setAccessible(true);
    $params = $paramsProperty->getValue($generator);
    expect($params['color'])->toBe($inputs['color']);
});
