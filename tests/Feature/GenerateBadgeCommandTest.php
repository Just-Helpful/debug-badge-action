<?php

/**
 * Copyright (c) 2025 Marcos Aurelio
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/macoaure/badge-action
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

    $outputPath = 'test-badges/test.svg';
    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => $outputPath,
        '--color' => 'green'
    ]);

    expect($result)->toBe(Command::SUCCESS);
    expect(file_exists($outputPath))->toBeTrue();
    expect($commandTester->getDisplay())->toContain("Badge generated successfully at: {$outputPath}");

    // Cleanup
    if (file_exists($outputPath)) {
        unlink($outputPath);
    }
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
    expect($commandTester->getDisplay())->toContain('Invalid color format: invalid-color');
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

test('it generates badge in the exact specified path', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $customPath = 'custom/path/test.svg';
    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => $customPath
    ]);

    expect($result)->toBe(Command::SUCCESS);
    expect(file_exists($customPath))->toBeTrue();
    expect($commandTester->getDisplay())->toContain("Badge generated successfully at: {$customPath}");

    // Cleanup
    if (file_exists($customPath)) {
        unlink($customPath);
    }
    if (is_dir(dirname($customPath))) {
        rmdir(dirname($customPath));
        rmdir(dirname(dirname($customPath)));
    }
});

test('it handles invalid logo color gracefully', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'foo',
        'status' => 'bar',
        'path' => 'baz',
        '--color' => 'blue',
        '--label-color' => '555',
        '--style' => 'flat',
        '--logo-color' => 'bad-color!', // use truly invalid value
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Invalid logo color format: bad-color!');
});
