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

#[AsCommand(
    name: 'badge',
    description: 'Generate a badge using shields.io API',
)]
class GenerateBadgeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('label', InputArgument::REQUIRED, 'The label text for the badge')
            ->addArgument('status', InputArgument::REQUIRED, 'The status text for the badge')
            ->addArgument('path', InputArgument::REQUIRED, 'The output path for the badge')
            ->addOption('color', null, InputOption::VALUE_OPTIONAL, 'The color of the badge', 'blue')
            ->addOption('label-color', null, InputOption::VALUE_OPTIONAL, 'The color of the label', 'grey')
            ->addOption('style', null, InputOption::VALUE_OPTIONAL, 'The style of the badge', 'flat-square')
            ->addOption('logo', null, InputOption::VALUE_OPTIONAL, 'The logo to use on the badge')
            ->addOption('logo-color', null, InputOption::VALUE_OPTIONAL, 'The color of the logo')
            ->addOption('cache-seconds', null, InputOption::VALUE_OPTIONAL, 'Cache control in seconds')
            ->addOption('link', null, InputOption::VALUE_OPTIONAL, 'The link to use for the badge')
            ->addOption('max-age', null, InputOption::VALUE_OPTIONAL, 'Max age in seconds');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Handle help and version flags
            if ($input->getOption('help')) {
                $output->writeln($this->getHelp());
                return Command::SUCCESS;
            }

            if ($input->getOption('version')) {
                $output->writeln('Badge Generator v1.0.0');
                return Command::SUCCESS;
            }

            // Validate required arguments
            $label = $input->getArgument('label');
            $status = $input->getArgument('status');
            $path = $input->getArgument('path');

            if (empty($label) || empty($status) || empty($path)) {
                throw new ValidationException('Not enough arguments (missing: "label, status, path")');
            }

            // Validate style option
            $validStyles = ['flat', 'flat-square', 'plastic', 'for-the-badge', 'social'];
            $style = $input->getOption('style');
            if (!in_array($style, $validStyles)) {
                throw new ValidationException("Invalid style: {$style}. Valid styles are: " . implode(', ', $validStyles));
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

            $inputs = $this->getInputs($input);
            $generator = BadgeGeneratorFactory::create($inputs);
            $path = $generator->generate();

            $io->success("Badge generated successfully at: {$path}");
            return Command::SUCCESS;
        } catch (ValidationException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (HttpClientException $e) {
            $io->error("Failed to download badge: " . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error("An unexpected error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getInputs(InputInterface $input): array
    {
        $inputs = [
            'label' => $input->getArgument('label'),
            'status' => $input->getArgument('status'),
            'path' => $input->getArgument('path'),
            'color' => $input->getOption('color'),
            'label-color' => $input->getOption('label-color'),
            'style' => $input->getOption('style'),
            'logo' => $input->getOption('logo'),
            'logo-color' => $input->getOption('logo-color'),
            'cache-seconds' => $input->getOption('cache-seconds'),
            'link' => $input->getOption('link'),
            'max-age' => $input->getOption('max-age'),
        ];

        return array_filter($inputs, fn($value) => $value !== null);
    }

    public function getHelp(): string
    {
        return <<<EOT
Generate a badge using shields.io API

Usage:
  badge [options] <label> <status> <path>

Arguments:
  label                  The left label of the badge
  status                 The right status of the badge
  path                   The file path to store the badge image file

Options:
  -c, --color=COLOR     Badge color(s). Can be hex, named colors, or comma-separated for gradient [default: "blue"]
  -l, --label-color=LABEL-COLOR
                        Label color (hex or named color) [default: "555"]
  -s, --style=STYLE     Badge style (flat, flat-square, plastic, for-the-badge, social) [default: "flat"]
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
