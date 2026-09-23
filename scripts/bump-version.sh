#!/bin/bash

# Version bump script for laravel-agent-native
# Usage: ./scripts/bump-version.sh <major|minor|patch> [--dry-run]

set -e

DRY_RUN=false
BUMP_TYPE=""

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        major|minor|patch)
            BUMP_TYPE="$1"
            shift
            ;;
        *)
            echo "Usage: $0 <major|minor|patch> [--dry-run]"
            exit 1
            ;;
    esac
done

if [ -z "$BUMP_TYPE" ]; then
    echo "❌ Error: Please specify major, minor, or patch"
    echo "Usage: $0 <major|minor|patch> [--dry-run]"
    exit 1
fi

# Check if we're on main branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "❌ Error: You must be on the main branch to bump versions"
    echo "Current branch: $CURRENT_BRANCH"
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo "❌ Error: You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Get current version from composer.json
CURRENT_VERSION=$(grep -Po '"version":\s*"\K[^"]*' composer.json | head -1)
if [ -z "$CURRENT_VERSION" ]; then
    echo "⚠️  No version found in composer.json. Using v1.0.0 as base."
    CURRENT_VERSION="1.0.0"
fi

echo "📦 Current version: $CURRENT_VERSION"

# Parse version components
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

# Calculate new version based on bump type
case "$BUMP_TYPE" in
    major)
        NEW_MAJOR=$((MAJOR + 1))
        NEW_VERSION="$NEW_MAJOR.0.0"
        ;;
    minor)
        NEW_MINOR=$((MINOR + 1))
        NEW_VERSION="$MAJOR.$NEW_MINOR.0"
        ;;
    patch)
        NEW_PATCH=$((PATCH + 1))
        NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH"
        ;;
esac

echo "🎯 New version: $NEW_VERSION"

if [ "$DRY_RUN" = true ]; then
    echo ""
    echo "=== DRY RUN ==="
    echo "This is what would happen:"
    echo "  1. Update composer.json version to: $NEW_VERSION"
    echo "  2. Create git tag: v$NEW_VERSION"
    echo "  3. Update CHANGELOG.md with version $NEW_VERSION"
    echo ""
    exit 0
fi

# Confirm with user
read -p "🔖 Proceed with version bump to $NEW_VERSION? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Aborted."
    exit 1
fi

# Update composer.json
sed -i "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" composer.json

# Create git tag
git add composer.json
git commit -m "chore: Bump version to $NEW_VERSION"
git tag -a "v$NEW_VERSION" -m "Release v$NEW_VERSION"

# Update CHANGELOG.md
DATE=$(date +%Y-%m-%d)
sed -i "s|## \[Unreleased\]|## [Unreleased]\n\n### Added\n- \n\n---\n\n## [$NEW_VERSION] - $DATE|" CHANGELOG.md

echo "✅ Successfully bumped to version $NEW_VERSION"
echo ""
echo "📋 Next steps:"
echo "  1. Review and edit CHANGELOG.md entries"
echo "  2. Commit changelog: git add CHANGELOG.md && git commit -m 'docs: Update changelog'"
echo "  3. Push changes: git push origin main && git push origin v$NEW_VERSION"
echo "  4. Update Packagist (should auto-sync via webhook)"
echo ""
echo "📦 Release notes draft:"
grep -A5 "## \[$NEW_VERSION\]" CHANGELOG.md | head -10
