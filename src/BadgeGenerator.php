<?php

namespace BadgeGenerator;

use BadgeGenerator\Contracts\BadgeGeneratorInterface;
use BadgeGenerator\Contracts\HttpClientInterface;
use BadgeGenerator\Contracts\UrlBuilderInterface;
use BadgeGenerator\Exceptions\ValidationException;

class BadgeGenerator implements BadgeGeneratorInterface
{
    private array $params = [];
    private string $label;
    private string $status;
    private string $path;

    public function __construct(
        private readonly UrlBuilderInterface $urlBuilder,
        private readonly HttpClientInterface $httpClient,
        array $inputs
    ) {
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

    public function generate(): string
    {
        $url = $this->urlBuilder->build($this->label, $this->status, $this->params);
        $content = $this->httpClient->download($url);

        $this->ensureDirectoryExists();
        $this->saveFile($content);

        return $this->path;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$dir}");
        }
    }

    private function saveFile(string $content): void
    {
        if (file_put_contents($this->path, $content) === false) {
            throw new \RuntimeException("Failed to save badge to: {$this->path}");
        }
    }
}
