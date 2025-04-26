<?php

namespace Tests\Feature;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

test('it can generate a badge via CLI', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
    ]);

    expect($commandTester->getStatusCode())->toBe(0);
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: test.svg');
});

test('it shows error when required arguments are missing', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'label' => 'test',
        'status' => null,
        'path' => null,
    ], ['catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(1);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "label, status, path")');
});

test('it handles validation errors gracefully', function () {
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

test('it handles HTTP client errors gracefully', function () {
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

test('it handles unexpected errors gracefully', function () {
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
