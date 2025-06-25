<?php

use BadgeGenerator\Command\GenerateBadgeCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Psr\Log\NullLogger;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Mockery as M;

it('adds processor and handler to logger in constructor', function () {
    $logger = new Logger('test');
    $command = new GenerateBadgeCommand($logger);
    $handlers = $logger->getHandlers();
    expect($handlers)->not->toBeEmpty();
    expect($logger->isHandling(Logger::INFO))->toBeTrue();
});

it('uses NullLogger if no logger is provided', function () {
    $command = new GenerateBadgeCommand();
    $reflection = new ReflectionClass($command);
    $property = $reflection->getProperty('logger');
    $property->setAccessible(true);
    $logger = $property->getValue($command);
    expect($logger)->toBeInstanceOf(NullLogger::class);
});

it('initialize skips for help and version', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getOption')->with('help')->andReturn(true);
    $input->shouldReceive('getOption')->with('version')->andReturn(false);
    $output = new NullOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('initialize');
    $method->setAccessible(true);
    expect($method->invoke($command, $input, $output))->toBeNull();
});

it('interact skips for help and version', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getOption')->with('help')->andReturn(false);
    $input->shouldReceive('getOption')->with('version')->andReturn(true);
    $output = new NullOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('interact');
    $method->setAccessible(true);
    expect($method->invoke($command, $input, $output))->toBeNull();
});

it('getInputs trims and filters', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('label')->andReturn('  foo  ');
    $input->shouldReceive('getArgument')->with('status')->andReturn('bar');
    $input->shouldReceive('getArgument')->with('path')->andReturn('   ');
    $input->shouldReceive('getOption')->with('color')->andReturn(' blue ');
    $input->shouldReceive('getOption')->with('label-color')->andReturn(null);
    $input->shouldReceive('getOption')->with('style')->andReturn('flat');
    $input->shouldReceive('getOption')->with('logo')->andReturn(null);
    $input->shouldReceive('getOption')->with('logo-color')->andReturn('');
    $input->shouldReceive('getOption')->with('cache-seconds')->andReturn(null);
    $input->shouldReceive('getOption')->with('link')->andReturn(null);
    $input->shouldReceive('getOption')->with('max-age')->andReturn(null);
    $inputs = (new ReflectionClass($command))
        ->getMethod('getInputs')
        ->invoke($command, $input);
    expect($inputs)->toBe([
        'label' => 'foo',
        'status' => 'bar',
        'color' => 'blue',
        'style' => 'flat',
    ]);
});

it('getArgumentsHelp returns string', function () {
    $command = new GenerateBadgeCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('getArgumentsHelp');
    $method->setAccessible(true);
    expect($method->invoke($command))->toContain('label');
});

it('getOptionsHelp returns string', function () {
    $command = new GenerateBadgeCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('getOptionsHelp');
    $method->setAccessible(true);
    expect($method->invoke($command))->toContain('--color');
});

it('fails on invalid style', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('label')->andReturn('foo');
    $input->shouldReceive('getArgument')->with('status')->andReturn('bar');
    $input->shouldReceive('getArgument')->with('path')->andReturn('baz');
    $input->shouldReceive('getOption')->with('help')->andReturn(false);
    $input->shouldReceive('getOption')->with('version')->andReturn(false);
    $input->shouldReceive('getOption')->with('style')->andReturn('invalid');
    $input->shouldReceive('getOption')->with('color')->andReturn('blue');
    $input->shouldReceive('getOption')->with('label-color')->andReturn('555');
    $input->shouldReceive('getOption')->with('logo')->andReturn(null);
    $input->shouldReceive('getOption')->with('logo-color')->andReturn(null);
    $input->shouldReceive('getOption')->with('cache-seconds')->andReturn(null);
    $input->shouldReceive('getOption')->with('link')->andReturn(null);
    $input->shouldReceive('getOption')->with('max-age')->andReturn(null);
    $output = new BufferedOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(true);
    $result = $method->invoke($command, $input, $output);
    expect($result)->toBe(1);
    expect($output->fetch())->toContain('Invalid style: invalid');
});

it('fails on invalid numeric option', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('label')->andReturn('foo');
    $input->shouldReceive('getArgument')->with('status')->andReturn('bar');
    $input->shouldReceive('getArgument')->with('path')->andReturn('baz');
    $input->shouldReceive('getOption')->with('help')->andReturn(false);
    $input->shouldReceive('getOption')->with('version')->andReturn(false);
    $input->shouldReceive('getOption')->with('style')->andReturn('flat');
    $input->shouldReceive('getOption')->with('color')->andReturn('blue');
    $input->shouldReceive('getOption')->with('label-color')->andReturn('555');
    $input->shouldReceive('getOption')->with('logo')->andReturn(null);
    $input->shouldReceive('getOption')->with('logo-color')->andReturn(null);
    $input->shouldReceive('getOption')->with('cache-seconds')->andReturn('notanumber');
    $input->shouldReceive('getOption')->with('link')->andReturn(null);
    $input->shouldReceive('getOption')->with('max-age')->andReturn(null);
    $output = new BufferedOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(true);
    $result = $method->invoke($command, $input, $output);
    expect($result)->toBe(1);
    expect($output->fetch())->toContain('Invalid cache-seconds: must be a number');
});

it('fails on invalid link', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('label')->andReturn('foo');
    $input->shouldReceive('getArgument')->with('status')->andReturn('bar');
    $input->shouldReceive('getArgument')->with('path')->andReturn('baz');
    $input->shouldReceive('getOption')->with('help')->andReturn(false);
    $input->shouldReceive('getOption')->with('version')->andReturn(false);
    $input->shouldReceive('getOption')->with('style')->andReturn('flat');
    $input->shouldReceive('getOption')->with('color')->andReturn('blue');
    $input->shouldReceive('getOption')->with('label-color')->andReturn('555');
    $input->shouldReceive('getOption')->with('logo')->andReturn(null);
    $input->shouldReceive('getOption')->with('logo-color')->andReturn(null);
    $input->shouldReceive('getOption')->with('cache-seconds')->andReturn(null);
    $input->shouldReceive('getOption')->with('link')->andReturn('not-a-url');
    $input->shouldReceive('getOption')->with('max-age')->andReturn(null);
    $output = new BufferedOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(true);
    $result = $method->invoke($command, $input, $output);
    expect($result)->toBe(1);
    expect($output->fetch())->toContain('Invalid link URL: not-a-url');
});

it('fails on invalid color', function () {
    $command = new GenerateBadgeCommand();
    $input = M::mock(InputInterface::class);
    $input->shouldReceive('getArgument')->with('label')->andReturn('foo');
    $input->shouldReceive('getArgument')->with('status')->andReturn('bar');
    $input->shouldReceive('getArgument')->with('path')->andReturn('baz');
    $input->shouldReceive('getOption')->with('help')->andReturn(false);
    $input->shouldReceive('getOption')->with('version')->andReturn(false);
    $input->shouldReceive('getOption')->with('style')->andReturn('flat');
    $input->shouldReceive('getOption')->with('color')->andReturn('invalid-color');
    $input->shouldReceive('getOption')->with('label-color')->andReturn('555');
    $input->shouldReceive('getOption')->with('logo')->andReturn(null);
    $input->shouldReceive('getOption')->with('logo-color')->andReturn(null);
    $input->shouldReceive('getOption')->with('cache-seconds')->andReturn(null);
    $input->shouldReceive('getOption')->with('link')->andReturn(null);
    $input->shouldReceive('getOption')->with('max-age')->andReturn(null);
    $output = new BufferedOutput();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(true);
    $result = $method->invoke($command, $input, $output);
    expect($result)->toBe(1);
    expect($output->fetch())->toContain('Invalid color format: invalid-color');
});

test('it fails on invalid logo color', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);
    $command = $application->find('badge:generate');
    $tester = new CommandTester($command);
    $result = $tester->execute([
        'label' => 'foo',
        'status' => 'bar',
        'path' => 'baz',
        '--color' => 'blue',
        '--label-color' => '555',
        '--style' => 'flat',
        '--logo-color' => 'bad-color!', // use truly invalid value
    ]);
    expect($result)->toBe(1);
    expect($tester->getDisplay())->toContain('Invalid logo color format: bad-color!');
});

test('it fails on empty arguments', function () {
    $application = new Application();
    $command = new GenerateBadgeCommand();
    $application->add($command);
    $command = $application->find('badge:generate');
    $tester = new CommandTester($command);
    $result = $tester->execute([
        'label' => '   ',
        'status' => '',
        'path' => '',
    ]);
    expect($result)->toBe(1);
    expect(trim($tester->getDisplay()))->toContain('Not enough arguments (missing: "status, path")');
});
