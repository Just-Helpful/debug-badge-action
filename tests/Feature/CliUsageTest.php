<?php

namespace Tests\Feature;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

test('it shows help message', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute(['--help' => true], ['catch_exceptions' => false]);

    expect($commandTester->getDisplay())
        ->toContain('Generate a badge using shields.io API')
        ->toContain('Usage:')
        ->toContain('badge:generate [options] <label> <status> <path>')
        ->toContain('Arguments:')
        ->toContain('Options:');
});

test('it shows version', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute(['--version' => true], ['catch_exceptions' => false]);

    expect($commandTester->getDisplay())
        ->toContain('Badge Generator');
});

test('it generates badge with all valid options', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--color' => 'green',
        '--label-color' => '000000',
        '--style' => 'flat-square',
        '--logo' => 'github',
        '--logo-color' => 'ffffff',
        '--cache-seconds' => '3600',
        '--link' => 'https://example.com',
        '--max-age' => '86400',
    ]);

    expect($commandTester->getStatusCode())->toBe(0);
    expect(file_exists('test.svg'))->toBeTrue();
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: test.svg');
});

test('it handles invalid color format', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--color' => 'invalid-color',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Failed to download badge');
});

test('it handles invalid style', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--style' => 'invalid-style',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Invalid style: invalid-style');
});

test('it handles invalid logo color format', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--logo' => 'github',
        '--logo-color' => 'invalid-color',
    ]);

    expect($commandTester->getStatusCode())->toBe(0);
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: test.svg');
});

test('it handles invalid cache seconds', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--cache-seconds' => 'invalid',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Invalid cache-seconds: must be a number');
});

test('it handles invalid max age', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--max-age' => 'invalid',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Invalid max-age: must be a number');
});

test('it handles invalid link url', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--link' => 'invalid-url',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Invalid link URL: invalid-url');
});

test('it handles missing required arguments', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "label, status, path")');
});

test('it handles empty required arguments', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => '',
        'status' => '',
        'path' => '',
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "label, status, path")');
});

test('it handles file permission errors', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => '/root/test.svg', // This will cause a permission error
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('An unexpected error occurred');
});
