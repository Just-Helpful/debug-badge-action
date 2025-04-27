<?php
/**
 * Tests for the GenerateBadgeCommand class.
 *
 * These tests verify that the command-line interface works correctly,
 * including input validation, error handling, and successful badge generation.
 */

namespace Tests\Feature;

use BadgeGenerator\Command\GenerateBadgeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;

beforeEach(function () {
    if (!is_dir('var/tmp')) {
        mkdir('var/tmp', 0777, true);
    }
});

afterEach(function () {
    if (is_dir('var/tmp')) {
        array_map('unlink', glob("var/tmp/*.*"));
    }
});

test('it can generate a badge via CLI', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--color' => 'green'
    ]);

    expect($result)->toBe(Command::SUCCESS);
    expect(file_exists('var/tmp/test.svg'))->toBeTrue();
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: var/tmp/test.svg');
});

test('it shows error when required arguments are missing', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([], ['interactive' => false, 'decorated' => false, 'catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "label, status, path")');
});

test('it handles validation errors gracefully', function () {
    $application = new Application();
    $application->add(new \BadgeGenerator\Command\GenerateBadgeCommand());

    $command = $application->find('badge:generate');
    $commandTester = new CommandTester($command);

    $result = $commandTester->execute([
        'label' => '',
        'status' => '',
        'path' => ''
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "label, status, path")');
});

test('it handles HTTP client errors gracefully', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--color' => 'invalid-color'
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Failed to download badge');
});

test('it supports logging to stdout', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg'
    ]);

    expect($result)->toBe(Command::SUCCESS);
    expect($commandTester->getDisplay())->toContain('Badge generated successfully');
});

test('it handles unexpected errors gracefully', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    // Create a read-only directory
    $testDir = 'var/tmp/readonly';
    if (!is_dir($testDir)) {
        mkdir($testDir, 0777, true);
    }
    chmod($testDir, 0444);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => $testDir . '/test.svg'
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Failed to save badge');

    // Cleanup
    chmod($testDir, 0777);
    rmdir($testDir);
});
