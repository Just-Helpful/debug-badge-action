<?php

namespace BadgeGenerator\HttpClient;

use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Exceptions\HttpClientException;

class CurlHttpClient implements HttpClientInterface
{
    public function download(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new HttpClientException("Failed to download badge: {$error}");
        }

        if ($httpCode !== 200) {
            throw new HttpClientException("Failed to download badge: HTTP {$httpCode}");
        }

        return $response;
    }
}
