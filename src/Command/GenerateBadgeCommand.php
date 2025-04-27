<?php

namespace BadgeGenerator\Command;

use BadgeGenerator\BadgeGeneratorFactory;
use BadgeGenerator\Exceptions\HttpClientException;
use BadgeGenerator\Exceptions\ValidationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;

#[AsCommand(
    name: 'badge:generate',
    description: 'Generate a badge using shields.io API',
)]
class GenerateBadgeCommand extends Command
{
    private const VERSION = '1.0.0';
    private LoggerInterface $logger;

    /**
     * Constructor for the GenerateBadgeCommand.
     *
     * @param LoggerInterface|null $logger PSR-3 logger interface
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct();
        $this->logger = $logger ?? new NullLogger();

        if ($this->logger instanceof Logger) {
            $this->logger->pushProcessor(function ($record) {
                $record['extra']['command'] = 'badge:generate';
                return $record;
            });

            // Add console output handler if not already present
            $hasConsoleHandler = false;
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof StreamHandler || $handler instanceof TestHandler) {
                    $hasConsoleHandler = true;
                    break;
                }
            }

            if (!$hasConsoleHandler) {
                $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
            }
        }
    }

    /**
     * Configures the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->setName('badge:generate')
            ->setDescription('Generate a badge using shields.io API')
            ->addArgument('label', InputArgument::OPTIONAL, 'The label text for the badge')
            ->addArgument('status', InputArgument::OPTIONAL, 'The status text for the badge')
            ->addArgument('path', InputArgument::OPTIONAL, 'The output path for the badge')
            ->addOption('color', 'c', InputOption::VALUE_OPTIONAL, 'The color of the badge', 'blue')
            ->addOption('label-color', 'l', InputOption::VALUE_OPTIONAL, 'The color of the label', '555')
            ->addOption('style', 's', InputOption::VALUE_OPTIONAL, 'The style of the badge', 'flat-square')
            ->addOption('logo', null, InputOption::VALUE_OPTIONAL, 'The logo to use on the badge')
            ->addOption('logo-color', null, InputOption::VALUE_OPTIONAL, 'The color of the logo')
            ->addOption('cache-seconds', null, InputOption::VALUE_OPTIONAL, 'Cache control in seconds')
            ->addOption('link', null, InputOption::VALUE_OPTIONAL, 'The link to use for the badge')
            ->addOption('max-age', null, InputOption::VALUE_OPTIONAL, 'Max age in seconds')
            ->addOption('help', 'h', InputOption::VALUE_NONE, 'Display this help message')
            ->addOption('version', 'V', InputOption::VALUE_NONE, 'Display this application version');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Skip validation for help and version options
        if ($input->getOption('help') || $input->getOption('version')) {
            return;
        }

        parent::initialize($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // Skip interaction for help and version options
        if ($input->getOption('help') || $input->getOption('version')) {
            return;
        }

        parent::interact($input, $output);
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input Command input
     * @param OutputInterface $output Command output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Handle help and version options first
        if ($input->getOption('help')) {
            $io->text($this->getHelp());
            return Command::SUCCESS;
        }

        if ($input->getOption('version')) {
            $io->text(self::VERSION);
            return Command::SUCCESS;
        }

        try {
            // Validate required arguments
            $missingArgs = [];
            foreach (['label', 'status', 'path'] as $arg) {
                if (!$input->getArgument($arg)) {
                    $missingArgs[] = $arg;
                }
            }

            if (!empty($missingArgs)) {
                throw new ValidationException(sprintf('Not enough arguments (missing: "%s").', implode(', ', $missingArgs)));
            }

            // Log command execution start
            $this->logger->info('Starting badge generation', [
                'label' => $input->getArgument('label'),
                'status' => $input->getArgument('status'),
                'path' => $input->getArgument('path')
            ]);

            // Validate empty arguments
            $emptyArgs = [];
            foreach (['label', 'status', 'path'] as $arg) {
                if (empty(trim($input->getArgument($arg)))) {
                    $emptyArgs[] = $arg;
                }
            }

            if (!empty($emptyArgs)) {
                throw new ValidationException('Not enough arguments (missing: "' . implode(', ', $emptyArgs) . '")');
            }

            // Validate style option
            $style = $input->getOption('style');
            $validStyles = ['flat', 'flat-square', 'plastic', 'for-the-badge', 'social'];
            if ($style && !in_array($style, $validStyles)) {
                throw new ValidationException("Invalid style: {$style}");
            }

            // Validate numeric options
            foreach (['cache-seconds', 'max-age'] as $option) {
                $value = $input->getOption($option);
                if ($value !== null && !is_numeric($value)) {
                    throw new ValidationException("Invalid {$option}: must be a number");
                }
            }

            // Validate URL
            $link = $input->getOption('link');
            if ($link !== null && !filter_var($link, FILTER_VALIDATE_URL)) {
                throw new ValidationException("Invalid link URL: {$link}");
            }

            // Validate logo color format
            $logoColor = $input->getOption('logo-color');
            if ($logoColor !== null) {
                $validColorFormats = [
                    '/^[a-zA-Z]+$/', // Named colors
                    '/^[0-9A-Fa-f]{3}$/', // RGB short
                    '/^[0-9A-Fa-f]{6}$/', // RGB full
                ];
                $isValidColor = false;
                foreach ($validColorFormats as $format) {
                    if (preg_match($format, $logoColor)) {
                        $isValidColor = true;
                        break;
                    }
                }
                if (!$isValidColor) {
                    throw new ValidationException("Invalid logo color format: {$logoColor}");
                }
            }

            // Create badge generator and generate badge
            $inputs = $this->getInputs($input);
            $this->logger->info('Creating badge generator with inputs', ['inputs' => $inputs]);

            $factory = new BadgeGeneratorFactory();
            $generator = $factory->create($inputs, $this->logger);
            $path = $generator->generate();

            $this->logger->info('Badge generated successfully', ['path' => $path]);
            $io->success(sprintf('Badge generated successfully at: %s', $path));
            return Command::SUCCESS;

        } catch (ValidationException $e) {
            $this->logger->error('Validation error', ['error' => $e->getMessage()]);
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (HttpClientException $e) {
            $this->logger->error('HTTP client error', ['error' => $e->getMessage()]);
            $io->error('Failed to download badge: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $io->error('Failed to save badge: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getInputs(InputInterface $input): array
    {
        $inputs = [
            'label' => $input->getArgument('label') ?? '',
            'status' => $input->getArgument('status') ?? '',
            'path' => $input->getArgument('path') ?? '',
            'color' => $input->getOption('color'),
            'label-color' => $input->getOption('label-color'),
            'style' => $input->getOption('style'),
            'logo' => $input->getOption('logo'),
            'logo-color' => $input->getOption('logo-color'),
            'cache-seconds' => $input->getOption('cache-seconds'),
            'link' => $input->getOption('link'),
            'max-age' => $input->getOption('max-age'),
        ];

        // Trim string values and filter out null/empty values
        return array_filter(
            array_map(
                function ($value) {
                    return is_string($value) ? trim($value) : $value;
                },
                $inputs
            ),
            function ($value) {
                return $value !== null && $value !== '';
            }
        );
    }

    public function getHelp(): string
    {
        return <<<EOT
Generate a badge using shields.io API

Usage:
  badge:generate [options] <label> <status> <path>

Arguments:
  label                  The left label of the badge
  status                 The right status of the badge
  path                   The file path to store the badge image file

Options:
  -c, --color=COLOR     Badge color(s). Can be hex, named colors, or comma-separated for gradient [default: "blue"]
  -l, --label-color=LABEL-COLOR
                        Label color (hex or named color) [default: "555"]
  -s, --style=STYLE     Badge style (flat, flat-square, plastic, for-the-badge, social) [default: "flat-square"]
  --logo=LOGO           Logo name from simple-icons
  --logo-color=LOGO-COLOR
                        Logo color (hex, rgb, rgba, hsl, hsla, or CSS named colors)
  --cache-seconds=CACHE-SECONDS
                        Cache duration in seconds
  --link=LINK           URL to link the badge to
  --max-age=MAX-AGE     Maximum age of the badge in seconds
  -h, --help            Display this help message
  -V, --version         Display this application version
EOT;
    }

    private function getArgumentsHelp(): string
    {
        return <<<EOT
  label                  The left label of the badge
  status                 The right status of the badge
  path                   The file path to store the badge image file
EOT;
    }

    private function getOptionsHelp(): string
    {
        return <<<EOT
  -c, --color=COLOR     Badge color(s). Can be hex, named colors, or comma-separated for gradient [default: "blue"]
  -l, --label-color=LABEL-COLOR
                        Label color (hex or named color) [default: "555"]
  -s, --style=STYLE     Badge style (flat, flat-square, plastic, for-the-badge, social) [default: "flat-square"]
  --logo=LOGO           Logo name from simple-icons
  --logo-color=LOGO-COLOR
                        Logo color (hex, rgb, rgba, hsl, hsla, or CSS named colors)
  --cache-seconds=CACHE-SECONDS
                        Cache duration in seconds
  --link=LINK           URL to link the badge to
  --max-age=MAX-AGE     Maximum age of the badge in seconds
  -h, --help            Display this help message
  -V, --version         Display this application version
EOT;
    }
}
