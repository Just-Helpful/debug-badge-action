<?php

namespace BadgeGenerator\Contracts;

interface BadgeGeneratorInterface
{
    public function generate(): string;
}
