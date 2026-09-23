# Changelog

All notable changes to `laravel-agent-native` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Optional `laravel/mcp` bridge for standard MCP server support
- `AgentNativeServer` for dynamic MCP tool registration
- `agent:mcp` install command for scaffolding MCP routes
- `InteractsWithAgent` trait for Livewire components
- `AgentBridge` for agent-triggered reactivity events
- `ActionPerformed` event for component state synchronization
- HTTP endpoint (`AgentToolsController`) with security hardening
- Kill switch (`enabled` config) and authorization hook support
- Artisan commands: `agent:inspect`, `agent:cache`, `agent:clear`, `agent:mcp`
- Full OpenAI/MCP JSON Schema compilation via reflection

### Changed
- Updated to support Laravel 13, PHP 8.4, and Livewire 4.x

### Removed
- None yet

---

## [1.1.0] - 2026-09-22

### Added
- Support for Livewire 4.x (alongside v3.x)
- Orchestra Testbench ^11.0 compatibility
- GitHub Actions CI/CD pipeline

### Changed
- Upgraded `orchestra/testbench` to support Laravel 13

### Fixed
- `agent:mcp` local server registration duplicate issue

---

## [1.0.1] - 2026-09-21

### Fixed
- Documentation typo in README Livewire version reference

---

## [1.0.0] - 2026-09-20

### Added
- MVP release with core features:
  - `#[AgentAction]`, `#[AgentParam]`, `#[AgentExpose]` attributes
  - `SchemaCompiler` for JSON Schema generation
  - `ActionRegistry` + `ActionExecutor` with container DI
  - `ActionResult` for structured error handling
  - Livewire integration (`InteractsWithAgent`, `AgentBridge`)
  - HTTP tool-call endpoint with authentication hooks
  - Security hardening: kill switch, authorize callback
  - CLI commands for inspection, caching, and MCP setup
  - Comprehensive test suite (52 tests, 164 assertions)
  - Full documentation suite (/docs/*.md)

[Unreleased]: https://github.com/nataondev/laravel-agent-native/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/nataondev/laravel-agent-native/releases/tag/v1.1.0
[1.0.1]: https://github.com/nataondev/laravel-agent-native/releases/tag/v1.0.1
[1.0.0]: https://github.com/nataondev/laravel-agent-native/releases/tag/v1.0.0
