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

use BadgeGenerator\Exceptions\HttpClientException;
use BadgeGenerator\HttpClient\CurlHttpClient;

test('it downloads content successfully', function () {
    $client = new CurlHttpClient();
    $url = 'https://img.shields.io/badge/test-passing-blue';

    $content = $client->download($url);
    expect($content)->toBeString();
    expect(strlen($content))->toBeGreaterThan(0);
});

test('it throws exception on curl error', function () {
    $client = new CurlHttpClient();
    $url = 'https://invalid-url-that-does-not-exist.com';

    expect(fn() => $client->download($url))
        ->toThrow(HttpClientException::class)
        ->and(fn() => $client->download($url))
        ->toThrow('Failed to download badge');
});

test('it throws exception on non-200 status code', function () {
    $client = new CurlHttpClient();
    $url = 'https://httpstat.us/404';

    expect(fn() => $client->download($url))
        ->toThrow(HttpClientException::class)
        ->and(fn() => $client->download($url))
        ->toThrow('Failed to download badge: HTTP 404');
});
