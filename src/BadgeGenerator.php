<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
 */

namespace BadgeGenerator;

use BadgeGenerator\Contracts\BadgeGeneratorInterface;
use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Contracts\UrlBuilderInterface;
use BadgeGenerator\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BadgeGenerator implements BadgeGeneratorInterface
{
    private array $params = [];
    private string $label;
    private string $status;
    private string $path;
    private LoggerInterface $logger;

    /**
     * Constructor for the BadgeGenerator class.
     *
     * @param UrlBuilderInterface $urlBuilder Service for building badge URLs
     * @param HttpClientInterface $httpClient HTTP client for downloading badges
     * @param array $inputs Badge generation parameters
     * @param LoggerInterface|null $logger PSR-3 logger interface
     */
    public function __construct(
        private readonly UrlBuilderInterface $urlBuilder,
        private readonly HttpClientInterface $httpClient,
        array $inputs,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->validateRequiredInputs($inputs);
        $this->label = $inputs['label'];
        $this->status = $inputs['status'];
        $this->path = $inputs['path'];
        $this->setOptionalParams($inputs);
    }

    private function validateRequiredInputs(array $inputs): void
    {
        $required = ['label', 'status', 'path'];
        foreach ($required as $field) {
            if (empty($inputs[$field])) {
                throw new ValidationException("Missing required input: {$field}");
            }
        }
    }

    private function setOptionalParams(array $inputs): void
    {
        $optionalParams = [
            'style' => 'flat',
            'label-color' => '555',
            'color' => 'blue',
            'logo' => null,
            'logo-color' => null,
            'cache-seconds' => null,
            'link' => null,
            'max-age' => null
        ];

        foreach ($optionalParams as $param => $default) {
            if (isset($inputs[$param]) && $inputs[$param] !== '') {
                $this->params[$param] = $inputs[$param];
            } elseif ($default !== null) {
                $this->params[$param] = $default;
            }
        }
    }

    /**
     * Generates a badge using the provided inputs.
     *
     * @return string Path to the generated badge file
     * @throws \Exception If badge generation fails
     */
    public function generate(): string
    {
        $this->logger->info('Generating badge', ['inputs' => $this->params]);

        // Build URL and download badge
        $url = $this->urlBuilder->build($this->label, $this->status, $this->params);
        $this->logger->debug('Built badge URL', ['url' => $url]);

        try {
            $content = $this->httpClient->download($url);
        } catch (\Exception $e) {
            $this->logger->error('Failed to download badge', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            throw $e;
        }

        return $this->saveBadgeToFile($content);
    }

    private function saveBadgeToFile(string $content): string
    {
        $outputPath = $this->path;
        $this->ensureOutputDirectoryExists();

        // Create a temporary file with a unique name
        $tempFile = $outputPath . '.tmp.' . uniqid('', true);

        if (file_put_contents($tempFile, $content) === false) {
            throw new \Exception('Failed to save badge: Could not write to temporary file');
        }

        // Set proper permissions on the temporary file
        @chmod($tempFile, 0666);
        clearstatcache(true, $tempFile);

        // Atomically move the temporary file to the target location
        if (!@rename($tempFile, $outputPath)) {
            @unlink($tempFile); // Clean up the temporary file
            throw new \Exception('Failed to save badge: Could not move temporary file to target location');
        }

        // Ensure final file has correct permissions
        @chmod($outputPath, 0666);
        clearstatcache(true, $outputPath);

        $this->logger->info('Badge saved successfully', ['path' => $outputPath]);
        return $outputPath;
    }

    private function ensureOutputDirectoryExists(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new \Exception('Failed to save badge: Could not create directory');
            }
            // Ensure directory has correct permissions after creation
            @chmod($dir, 0777);
            clearstatcache(true, $dir);
        }

        if (!is_writable($dir)) {
            throw new \Exception('Failed to save badge: Directory is not writable');
        }
    }
}

// Add a namespaced file_put_contents function for testability
if (!function_exists(__NAMESPACE__ . '\\file_put_contents')) {
    function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        return \file_put_contents($filename, $data, $flags, $context);
    }
}
