<?php

declare(strict_types=1);

use Ergebnis\License;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$license = License\Type\MIT::text(
    __DIR__ . '/LICENSE',
    License\Range::since(
        License\Year::fromString(date('Y')),
        new DateTimeZone('UTC')
    ),
    License\Holder::fromString('Marcos Aurelio'),
    License\Url::fromString('https://github.com/macoaure/badge-action')
);

$license->save();
$finder = new Finder();
$finder = $finder->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new Config())
    ->setFinder($finder)
    ->setRules([
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => trim($license->header()),
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ],
    ]);
