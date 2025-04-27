<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace BadgeGenerator\Services;

use BadgeGenerator\Contracts\UrlBuilderInterface;

class ShieldsIoUrlBuilder implements UrlBuilderInterface
{
    private string $baseUrl = 'https://img.shields.io/badge';

    /**
     * Build a shields.io URL with the given parameters.
     *
     * @param string $label The badge label
     * @param string $status The badge status/value
     * @param array $params Additional parameters
     * @return string The complete URL
     */
    public function build(string $label, string $status, array $params): string
    {
        $encodedLabel = $this->encodeParameter($label);
        $encodedStatus = $this->encodeParameter($status);
        $color = $params['color'] ?? 'blue';

        $url = "{$this->baseUrl}/{$encodedLabel}-{$encodedStatus}-{$color}";

        $queryParams = $this->buildQueryParams($params);
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }

        return $url;
    }

    /**
     * Encode a parameter for use in the shields.io URL.
     * This handles special characters and follows shields.io's encoding rules.
     *
     * @param string $str The string to encode
     * @return string The encoded string
     */
    private function encodeParameter(string $str): string
    {
        // First, URL encode the entire string
        $encoded = urlencode($str);

        // Replace specific characters according to shields.io rules
        return str_replace(
            ['%20', '%', '_', '-', '.'],
            [' ', '%25', '__', '--', '.'],
            $encoded
        );
    }

    /**
     * Build query parameters for the URL.
     *
     * @param array $params The parameters to build
     * @return array The built query parameters
     */
    private function buildQueryParams(array $params): array
    {
        $queryParams = [];
        $paramMap = [
            'label-color' => 'labelColor',
            'cache-seconds' => 'cacheSeconds',
            'max-age' => 'maxAge',
            'logo-color' => 'logoColor'
        ];

        foreach ($params as $key => $value) {
            if ($key !== 'color' && $value !== null) {
                $paramName = $paramMap[$key] ?? $key;
                $queryParams[] = urlencode($paramName) . '=' . urlencode($value);
            }
        }

        return $queryParams;
    }
}
