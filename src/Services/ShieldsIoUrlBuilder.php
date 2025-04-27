<?php

namespace BadgeGenerator\Services;

use BadgeGenerator\Contracts\UrlBuilderInterface;

class ShieldsIoUrlBuilder implements UrlBuilderInterface
{
    private string $baseUrl = 'https://img.shields.io/badge';

    public function build(string $label, string $status, array $params): string
    {
        $encodedLabel = $this->urlEncode($label);
        $encodedStatus = $this->urlEncode($status);
        $color = $params['color'] ?? 'blue';

        $url = "{$this->baseUrl}/{$encodedLabel}-{$encodedStatus}-{$color}";

        $queryParams = $this->buildQueryParams($params);
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }

        return $url;
    }

    private function urlEncode(string $str): string
    {
        return str_replace(
            [' ', '_', '-'],
            ['%20', '__', '--'],
            $str
        );
    }

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
