<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace Tests\Unit\HttpClient;

use BadgeGenerator\Exceptions\HttpClientException;
use BadgeGenerator\HttpClient\CurlHttpClient;

test('it downloads content successfully', function () {
    $client = new class extends CurlHttpClient {
        protected function executeCurl($ch)
        {
            return '<svg>test badge</svg>';
        }
        protected function getHttpCode($ch): int
        {
            return 200;
        }
        protected function getCurlError($ch): string
        {
            return '';
        }
    };

    $result = $client->download('https://example.com/badge.svg');
    expect($result)->toBe('<svg>test badge</svg>');
});

test('it throws exception on invalid URL', function () {
    $client = new class extends CurlHttpClient {
        protected function executeCurl($ch)
        {
            return false;
        }
        protected function getCurlError($ch): string
        {
            return 'Could not resolve host: invalid.domain';
        }
        protected function getHttpCode($ch): int
        {
            return 0;
        }
    };

    try {
        $client->download('https://invalid.domain/badge.svg');
        expect(false)->toBeTrue('Exception was not thrown');
    } catch (HttpClientException $e) {
        expect($e->getMessage())->toBe('Failed to download badge: Could not resolve host: invalid.domain');
    }
});

test('it throws exception on HTTP error', function () {
    $client = new class extends CurlHttpClient {
        protected function executeCurl($ch)
        {
            return 'Internal Server Error';
        }
        protected function getHttpCode($ch): int
        {
            return 500;
        }
        protected function getCurlError($ch): string
        {
            return '';
        }
    };

    try {
        $client->download('https://example.com/badge.svg');
        expect(false)->toBeTrue('Exception was not thrown');
    } catch (HttpClientException $e) {
        expect($e->getMessage())->toBe('Failed to download badge: HTTP 500');
    }
});

test('it handles network timeouts', function () {
    $client = new class extends CurlHttpClient {
        protected function executeCurl($ch)
        {
            return false;
        }
        protected function getCurlError($ch): string
        {
            return 'Operation timed out after 30001 milliseconds with 0 bytes received';
        }
        protected function getHttpCode($ch): int
        {
            return 0;
        }
    };

    try {
        $client->download('https://example.com/badge.svg');
        expect(false)->toBeTrue('Exception was not thrown');
    } catch (HttpClientException $e) {
        expect($e->getMessage())->toBe('Failed to download badge: Operation timed out after 30001 milliseconds with 0 bytes received');
    }
});
