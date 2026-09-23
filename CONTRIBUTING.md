# Contributing to laravel-agent-native

Thank you for your interest in contributing! This document outlines how to contribute to this project.

## 📋 Code of Conduct

This project follows our [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before contributing.

## 🚀 Quick Start

1. **Fork the repository** on GitHub
2. **Clone your fork**: `git clone git@github.com:yourusername/laravel-agent-native.git`
3. **Install dependencies**: `composer install`
4. **Run tests**: `vendor/bin/pest` (should pass all 52 tests)
5. **Make your changes** (see guidelines below)
6. **Submit a PR**

## 🛠 Development Workflow

### Branch Strategy

- `main`: Production-ready code (protected)
- `develop`: Integration branch for upcoming releases
- Feature branches: `<feature>/<description>` or `<fix>/<description>`

Example: `git checkout -b feature/mcp-server-support develop`

### Testing Requirements

Before submitting any contribution:

```bash
# Run full test suite
vendor/bin/pest

# Run style check
vendor/bin/pint --test

# Run parallel tests
vendor/bin/pest --parallel

# Check PHP syntax
php -l src/YourFile.php
```

All tests must pass before merging.

### Code Style

We use **Laravel Pint** as our style linter. Ensure your code conforms:

```bash
vendor/bin/pint
vendor/bin/pint --test  # verify before commit
```

#### Standards

- PSR-12 compliant
- Strict types (`declare(strict_types=1);`)
- No trailing whitespace
- Proper indentation (4 spaces)
- Consistent naming conventions

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
feat: add MCP server bridge
fix: resolve Livewire 4.x compatibility issue
docs: update README installation instructions
refactor: optimize schema compilation
test: add coverage for enum coercion
chore: update composer.json dependencies
```

### Pull Request Guidelines

1. **Title**: Use conventional commit format
   - ✅ `feat: add per-action authorization hooks`
   - ❌ `Added new feature`

2. **Description**: Include:
   - What changed
   - Why it changed
   - Any breaking changes
   - Screenshots if UI-related

3. **Testing**: Mention how you tested:
   - Which scenarios covered
   - Test cases added/modified
   - Manual testing steps

4. **Screenshots**: For UI or user-facing changes

5. **References**: Link related issues

## 🔧 Common Tasks

### Adding New Features

1. Create a feature branch from `develop`
2. Write tests first (TDD)
3. Implement the feature
4. Update documentation
5. Verify all tests pass

### Fixing Bugs

1. Create a reproduction scenario
2. Write a failing test
3. Fix the bug
4. Verify test passes
5. Add regression test

### Updating Documentation

Documentation lives in `/docs`. When updating:

1. Keep consistent with existing docs structure
2. Include relevant code examples
3. Link related documentation pages
4. Update README if external-facing change

## 🐛 Reporting Issues

Use [GitHub Issues](https://github.com/nataondev/laravel-agent-native/issues) with:

- **Bug reports**: Template includes environment, reproduction steps, expected vs actual behavior
- **Feature requests**: Clear description, use case, potential alternatives
- **Questions**: General inquiries about usage

## 📦 Code Review

All PRs require review. Reviewers will check:

- ✅ Tests pass
- ✅ Style guidelines met
- ✅ Code quality & maintainability
- ✅ Documentation updated
- ✅ Security implications considered
- ✅ Backward compatibility (if applicable)

## 🎯 Areas We Need Help With

- 🐛 Bug fixes
- 📖 Documentation improvements
- 🧪 More edge-case tests
- 🔍 Performance optimizations
- 🏷 Type annotations
- 🔄 Migration paths for future versions

##  Thank You!

Contributors are acknowledged in the changelog and README. Your contributions make this package better for everyone!

---

**Questions?** Reach out via [GitHub Discussions](https://github.com/nataondev/laravel-agent-native/discussions) or open an issue.
