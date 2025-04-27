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
