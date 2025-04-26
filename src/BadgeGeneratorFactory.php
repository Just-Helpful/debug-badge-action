<?php

namespace BadgeGenerator;

use BadgeGenerator\HttpClient\CurlHttpClient;
use BadgeGenerator\Services\ShieldsIoUrlBuilder;

class BadgeGeneratorFactory
{
    public static function create(array $inputs): BadgeGenerator
    {
        return new BadgeGenerator(
            new ShieldsIoUrlBuilder(),
            new CurlHttpClient(),
            $inputs
        );
    }
}
