<?php

namespace BadgeGenerator\Contracts;

interface HttpClientInterface
{
    public function download(string $url): string;
}
