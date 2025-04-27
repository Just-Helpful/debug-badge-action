<?php
/**
 * Factory for creating BadgeGenerator instances.
 *
 * This factory provides a convenient way to create BadgeGenerator instances
 * with all required dependencies properly configured.
 */

namespace BadgeGenerator;

use BadgeGenerator\HttpClient\CurlHttpClient;
use BadgeGenerator\Services\ShieldsIoUrlBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BadgeGeneratorFactory
{
    /**
     * Creates a new BadgeGenerator instance with all required dependencies.
     *
     * @param array $inputs Badge generation parameters
     * @param LoggerInterface|null $logger PSR-3 logger interface
     * @return BadgeGenerator
     */
    public static function create(array $inputs, ?LoggerInterface $logger = null): BadgeGenerator
    {
        $logger = $logger ?? new NullLogger();

        return new BadgeGenerator(
            new ShieldsIoUrlBuilder(),
            new CurlHttpClient(),
            $inputs,
            $logger
        );
    }
}
