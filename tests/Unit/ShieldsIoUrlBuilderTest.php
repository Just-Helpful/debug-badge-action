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

use BadgeGenerator\Services\ShieldsIoUrlBuilder;

test('it builds basic URL correctly', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('test', 'passing', ['color' => 'green']);

    expect($url)->toBe('https://img.shields.io/badge/test-passing-green');
});

test('it handles spaces in label and status', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('build status', 'all passing', ['color' => 'green']);

    expect($url)->toBe('https://img.shields.io/badge/build status-all passing-green');
});

test('it handles percentage signs correctly', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('coverage', '95%', ['color' => 'brightgreen']);

    expect($url)->toBe('https://img.shields.io/badge/coverage-95%25-brightgreen');
});

test('it handles multiple special characters', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('complex test', '75% & passing!', ['color' => 'blue']);

    expect($url)->toBe('https://img.shields.io/badge/complex test-75%25 %26 passing%21-blue');
});

test('it handles underscores and dashes', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('test_value', 'value-test', ['color' => 'green']);

    expect($url)->toBe('https://img.shields.io/badge/test__value-value--test-green');
});

test('it handles query parameters correctly', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('test', 'passing', [
        'color' => 'green',
        'style' => 'flat-square',
        'logo' => 'github',
        'logo-color' => 'white'
    ]);

    expect($url)->toContain('https://img.shields.io/badge/test-passing-green')
        ->toContain('style=flat-square')
        ->toContain('logo=github')
        ->toContain('logoColor=white');
});

test('it handles dots in version numbers', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('version', 'v1.2.3', ['color' => 'blue']);

    expect($url)->toBe('https://img.shields.io/badge/version-v1.2.3-blue');
});

test('it handles unicode characters', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('测试', '通过', ['color' => 'green']);

    expect($url)->toContain(urlencode('测试'))
        ->toContain(urlencode('通过'))
        ->toContain('green');
});

test('it handles empty optional parameters', function () {
    $builder = new ShieldsIoUrlBuilder();
    $url = $builder->build('test', 'passing', [
        'color' => 'green',
        'logo' => null,
        'style' => ''
    ]);

    expect($url)->toBe('https://img.shields.io/badge/test-passing-green');
});
