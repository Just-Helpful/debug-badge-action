# Dynamic Badge Generator Action

This GitHub Action generates dynamic badges using shields.io's API. It provides a flexible way to create and update badges in your repository with various customization options.

## Features

- Generate badges with custom labels and status
- Customize colors, styles, and other visual aspects
- Support for various badge styles (flat, flat-square, plastic, for-the-badge, social)
- Cache control options
- Link support for clickable badges

## Installation

```bash
composer require macoaure/badge-action
```

## Usage

### Command Line

The badge generator can be used directly from the command line:

```bash
php bin/badge <label> <status> <output-path> [options]
```

#### Arguments

- `label`: The text to display on the left side of the badge
- `status`: The text to display on the right side of the badge
- `output-path`: The path where the SVG file will be saved

#### Options

- `--color`: The color of the right side (default: brightgreen)
- `--style`: The badge style (default: flat)
  - Available styles: flat, flat-square, plastic, for-the-badge, social
- `--cache-seconds`: Cache duration in seconds
- `--max-age`: Maximum age of the badge in seconds
- `--link`: URL to make the badge clickable

#### Examples

Generate a simple passing badge:
```bash
php bin/badge "Test" "Passing" "test.svg"
```

Generate a custom styled badge:
```bash
php bin/badge "Coverage" "85%" "coverage.svg" --color=blue --style=flat-square
```

Generate a clickable badge:
```bash
php bin/badge "Documentation" "Online" "docs.svg" --link="https://docs.example.com"
```

### GitHub Action

```yaml
name: Generate Badge
on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  generate-badge:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Generate Badge
        uses: macoaure/badge-action@v1
        with:
          label: "Test"
          status: "Passing"
          output-path: "test.svg"
          color: "green"
          style: "flat-square"
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
