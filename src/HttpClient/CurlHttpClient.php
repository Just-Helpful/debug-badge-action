<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace BadgeGenerator\HttpClient;

use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Exceptions\HttpClientException;

class CurlHttpClient implements HttpClientInterface
{
    public function download(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Set timeout to 5 seconds for faster test feedback

        $response = $this->executeCurl($ch);
        $error = $this->getCurlError($ch);
        $httpCode = $this->getHttpCode($ch);

        curl_close($ch);

        if ($response === false) {
            throw new HttpClientException("Failed to download badge: {$error}");
        }

        if ($httpCode !== 200) {
            throw new HttpClientException("Failed to download badge: HTTP {$httpCode}");
        }

        return $response;
    }

    protected function executeCurl($ch)
    {
        return curl_exec($ch);
    }

    protected function getCurlError($ch)
    {
        return curl_error($ch);
    }

    protected function getHttpCode($ch)
    {
        return curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
}
