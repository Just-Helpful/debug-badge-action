<?php

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = $this->executeCurl($ch);
        $error = $this->getCurlError($ch);
        $httpCode = $this->getHttpCode($ch);

        curl_close($ch);

        if ($response === false) {
            throw new HttpClientException("Failed to download badge: {$error}");
        }

        if ($httpCode !== 200) {
            if ($httpCode === 422) {
                throw new HttpClientException("Failed to download badge: Invalid parameters provided");
            }
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
