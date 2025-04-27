# Dynamic Badge Generator Action

[![Build Status](https://raw.githubusercontent.com/macoaure/badge-action/main/badges/build.svg)](https://github.com/macoaure/badge-action/actions)
[![Version](https://raw.githubusercontent.com/macoaure/badge-action/main/badges/version.svg)](https://github.com/macoaure/badge-action/releases)
[![License](https://raw.githubusercontent.com/macoaure/badge-action/main/badges/license.svg)](LICENSE)
[![Code Coverage](https://raw.githubusercontent.com/macoaure/badge-action/main/badges/coverage.svg)](https://github.com/macoaure/badge-action)

A GitHub Action for generating dynamic SVG badges using [Shields.io](https://shields.io). Create and update beautiful, customizable badges directly in your GitHub repository.

## 🌟 Features

- 🎨 **Fully Customizable**: Colors, styles, logos, and more
- 🔄 **Dynamic Updates**: Automatically update badges on repository changes
- 🎯 **Multiple Badge Styles**: Support for flat, flat-square, plastic, for-the-badge, and social styles
- 🔒 **Cache Control**: Configure badge caching for optimal performance
- 🔗 **Clickable Badges**: Add links to make badges interactive
- 🛡️ **Powered by Shields.io**: Built on the reliable Shields.io API

## 📋 Quick Start

1. Create a badges directory in your repository:
```bash
mkdir -p badges
```

2. Add this to your GitHub workflow:
```yaml
- name: Generate Badge
  uses: macoaure/badge-action@v1
  with:
    label: "build"
    status: "passing"
    path: "badges/build.svg"
    color: "green"
    style: "flat-square"
```

3. Reference your badge in your README:
```markdown
[![Build Status](https://raw.githubusercontent.com/USER/REPO/BRANCH/badges/build.svg)](https://github.com/USER/REPO/actions)
```

## 🎯 Use Cases

### Build Status Badge
```yaml
- name: Generate Build Badge
  uses: macoaure/badge-action@v1
  with:
    label: "build"
    status: "passing"
    path: "badges/build.svg"
    color: "green"
    logo: "github"
```

### Version Badge
```yaml
- name: Generate Version Badge
  uses: macoaure/badge-action@v1
  with:
    label: "version"
    status: ${{ github.ref_name }}
    path: "badges/version.svg"
    color: "blue"
    style: "flat-square"
```

### Coverage Badge
```yaml
- name: Generate Coverage Badge
  uses: macoaure/badge-action@v1
  with:
    label: "coverage"
    status: "95%"
    path: "badges/coverage.svg"
    color: "brightgreen"
    logo: "codecov"
```

## 📝 Inputs

| Input | Description | Required | Default |
|-------|-------------|----------|---------|
| `label` | Left side text | Yes | - |
| `status` | Right side text | Yes | - |
| `path` | Output file path | Yes | - |
| `color` | Badge color | No | `blue` |
| `label-color` | Left side color | No | `555` |
| `style` | Badge style | No | `flat-square` |
| `logo` | Logo from simple-icons.org | No | - |
| `logo-color` | Logo color | No | - |
| `cache-seconds` | Cache duration | No | - |
| `link` | Clickable URL | No | - |
| `max-age` | Maximum cache age | No | - |

## 🎨 Available Styles

- `flat` - Flat style
- `flat-square` - Flat square style
- `plastic` - 3D plastic style
- `for-the-badge` - Bold, wider style
- `social` - Social media style

## 🔄 Complete Workflow Example

```yaml
name: Generate Badges

on:
  push:
    branches: [main]

jobs:
  generate-badges:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      # Optional: Calculate coverage if needed
      - name: Calculate coverage
        id: coverage
        run: |
          # Your coverage calculation here
          echo "coverage=95%" >> $GITHUB_OUTPUT

      - name: Generate Build Badge
        uses: macoaure/badge-action@v1
        with:
          label: "build"
          status: "passing"
          path: "badges/build.svg"
          color: "green"
          style: "flat-square"
          logo: "github"

      - name: Generate Coverage Badge
        uses: macoaure/badge-action@v1
        with:
          label: "coverage"
          status: ${{ steps.coverage.outputs.coverage }}
          path: "badges/coverage.svg"
          color: "brightgreen"
          logo: "codecov"

      # Commit and push the badges
      - name: Commit changes
        run: |
          git config --global user.name "github-actions[bot]"
          git config --global user.email "github-actions[bot]@users.noreply.github.com"
          git add badges/
          git commit -m "🔄 Update repository badges"
          git push
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- Badge generation powered by [Shields.io](https://shields.io)
- Icons provided by [Simple Icons](https://simpleicons.org)

## 🔍 Related

- [Shields.io](https://shields.io) - The service powering our badge generation
- [Simple Icons](https://simpleicons.org) - The icons used in badges
