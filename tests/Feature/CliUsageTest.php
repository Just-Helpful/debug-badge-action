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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use org\bovigo\vfs\vfsStream;
use BadgeGenerator\Command\GenerateBadgeCommand;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;

beforeEach(function () {
    // Ensure var/tmp directory exists
    if (!is_dir('var/tmp')) {
        mkdir('var/tmp', 0777, true);
    }
});

afterEach(function () {
    // Clean up temporary files
    if (is_dir('var/tmp')) {
        array_map('unlink', glob("var/tmp/*.*"));
    }
});

test('it shows help message', function () {
    $application = new Application();
    $command = new \BadgeGenerator\Command\GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute(['--help' => true], ['interactive' => false, 'decorated' => false]);

    $display = $commandTester->getDisplay();
    expect($display)
        ->toContain('Generate a badge using shields.io API')
        ->toContain('Usage:')
        ->toContain('Arguments:')
        ->toContain('Options:');
});

test('it shows version', function () {
    $application = new Application();
    $command = new \BadgeGenerator\Command\GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--version' => true
    ]);

    expect($commandTester->getDisplay())->toContain('1.0.0');
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

    expect($commandTester->getStatusCode())->toBe(Command::SUCCESS);
    expect(file_exists('var/tmp/test.svg'))->toBeTrue();
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: var/tmp/test.svg');
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
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--logo' => 'github',
        '--logo-color' => 'invalid-color'
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Invalid logo color format: invalid-color');
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
    $command = new \BadgeGenerator\Command\GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([], ['interactive' => false, 'decorated' => false, 'catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(Command::FAILURE);
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
    // Create a read-only directory
    $testDir = 'var/tmp/test_readonly';
    if (!is_dir($testDir)) {
        mkdir($testDir, 0777, true);
    }
    chmod($testDir, 0444);

    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

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

test('it fails when required arguments are missing', function () {
    $application = new Application();
    $command = new \BadgeGenerator\Command\GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute(['label' => 'test'], ['interactive' => false, 'decorated' => false, 'catch_exceptions' => false]);

    expect($commandTester->getStatusCode())->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Not enough arguments (missing: "status, path")');
});

test('it generates badge in var/tmp directory', function () {
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
    expect(file_exists('var/tmp/test.svg'))->toBeTrue();
    expect($commandTester->getDisplay())->toContain('Badge generated successfully at: var/tmp/test.svg');
});

test('it validates input using ArrayInput', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $input = new ArrayInput([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg',
        '--color' => 'invalid-color'
    ], $command->getDefinition());

    $output = new BufferedOutput();
    $result = $command->run($input, $output);

    expect($result)->toBe(Command::FAILURE);
    expect($output->fetch())->toContain('Failed to download badge');
});

test('it logs operations to stdout in development', function () {
    $testHandler = new TestHandler();
    $logger = new Logger('badge-generator');
    $logger->pushHandler($testHandler);

    $application = new Application();
    $command = new GenerateBadgeCommand($logger);
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'test.svg'
    ]);

    expect($testHandler->hasInfoRecords())->toBeTrue();
    expect($testHandler->hasRecord('Starting badge generation', Level::Info))->toBeTrue();
    expect($commandTester->getDisplay())->toContain('Badge generated successfully');
});

test('it handles special characters in label and status', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test/with/slashes',
        'status' => 'passing with spaces',
        'path' => 'test.svg'
    ]);

    expect($result)->toBe(Command::SUCCESS);
    expect(file_exists('var/tmp/test.svg'))->toBeTrue();
});

test('it validates directory permissions before generating badge', function () {
    // Create a read-only directory
    if (!is_dir('var/tmp/readonly')) {
        mkdir('var/tmp/readonly', 0777, true);
    }
    chmod('var/tmp/readonly', 0444);

    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $result = $commandTester->execute([
        'label' => 'test',
        'status' => 'passing',
        'path' => 'readonly/test.svg'
    ]);

    expect($result)->toBe(Command::FAILURE);
    expect($commandTester->getDisplay())->toContain('Failed to save badge');

    // Cleanup
    chmod('var/tmp/readonly', 0777);
    rmdir('var/tmp/readonly');
});
