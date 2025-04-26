#!/bin/bash

# Script to generate progressive commits with gitmoji commit message patterns
# Usage: ./scripts/generate-commits.sh

set -e

# Create badges directory if it doesn't exist
mkdir -p badges

# Function to generate a badge and commit it
generate_and_commit() {
  local label=$1
  local status=$2
  local path=$3
  local color=$4
  local style=$5
  local logo=$6
  local logo_color=$7
  local commit_message=$8

  echo "Generating badge: $label - $status"

  # Generate the badge
  php bin/badge "$label" "$status" "$path" \
    --color "$color" \
    --style "$style" \
    ${logo:+--logo "$logo"} \
    ${logo_color:+--logo-color "$logo_color"}

  # Commit the badge
  git add "$path"
  git commit -m "$commit_message"

  echo "✅ Committed: $commit_message"
  echo ""
}

# Check if we're in a git repository
if ! git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
  echo "Error: Not in a git repository"
  exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
  echo "Error: PHP is not installed"
  exit 1
fi

# Check if the badge binary exists
if [ ! -f "bin/badge" ]; then
  echo "Error: bin/badge not found. Make sure you're in the root directory of the project."
  exit 1
fi

# Make the badge binary executable
chmod +x bin/badge

echo "🚀 Starting badge generation and commit process..."
echo ""

# 1. Initial commit - 🎉 Initial commit
generate_and_commit \
  "build" "passing" "badges/build.svg" \
  "green" "flat-square" "github" "white" \
  "🎉 Initial commit"

# 2. Add feature - ✨ Add build status badge
generate_and_commit \
  "build" "passing" "badges/build.svg" \
  "green" "flat-square" "github" "white" \
  "✨ Add build status badge"

# 3. Fix bug - 🐛 Fix badge generation
generate_and_commit \
  "build" "passing" "badges/build.svg" \
  "green" "flat-square" "github" "white" \
  "🐛 Fix badge generation"

# 4. Update documentation - 📝 Update README with badge examples
generate_and_commit \
  "docs" "updated" "badges/docs.svg" \
  "blue" "flat-square" "github" "white" \
  "📝 Update README with badge examples"

# 5. Performance improvement - ⚡️ Improve badge generation speed
generate_and_commit \
  "performance" "improved" "badges/performance.svg" \
  "yellow" "flat-square" "github" "white" \
  "⚡️ Improve badge generation speed"

# 6. Refactor code - ♻️ Refactor badge generation code
generate_and_commit \
  "refactor" "completed" "badges/refactor.svg" \
  "orange" "flat-square" "github" "white" \
  "♻️ Refactor badge generation code"

# 7. Add test - ✅ Add tests for badge generation
generate_and_commit \
  "tests" "passing" "badges/tests.svg" \
  "brightgreen" "flat-square" "github" "white" \
  "✅ Add tests for badge generation"

# 8. Update dependencies - ⬆️ Update dependencies
generate_and_commit \
  "dependencies" "updated" "badges/dependencies.svg" \
  "blue" "flat-square" "github" "white" \
  "⬆️ Update dependencies"

# 9. Security fix - 🔒 Fix security vulnerability
generate_and_commit \
  "security" "fixed" "badges/security.svg" \
  "red" "flat-square" "github" "white" \
  "🔒 Fix security vulnerability"

# 10. Add new feature - ✨ Add custom badge styles
generate_and_commit \
  "features" "added" "badges/features.svg" \
  "purple" "flat-square" "github" "white" \
  "✨ Add custom badge styles"

# 11. Fix CI - 👷 Fix CI pipeline
generate_and_commit \
  "ci" "fixed" "badges/ci.svg" \
  "orange" "flat-square" "github" "white" \
  "👷 Fix CI pipeline"

# 12. Update version - 🔖 Release v1.0.0
generate_and_commit \
  "version" "1.0.0" "badges/version.svg" \
  "blue" "flat-square" "github" "white" \
  "🔖 Release v1.0.0"

echo "✅ All badges generated and committed successfully!"
echo "Total commits: 12"
