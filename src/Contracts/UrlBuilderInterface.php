<?php

namespace BadgeGenerator\Contracts;

interface UrlBuilderInterface
{
    public function build(string $label, string $status, array $params): string;
}
