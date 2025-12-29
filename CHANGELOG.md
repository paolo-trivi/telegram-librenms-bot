# Changelog

All notable changes to LibreBot will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2025-12-29

### Added
- **Internationalization (I18n)**: Full multilingual support with Symfony Translation
  - 6 languages: English (default), Italian, French, Spanish, German, Portuguese
  - Centralized translation keys in `lang/` directory
  - Dynamic language switching via `config.php`
- **Version Management**: Centralized version control system
  - `Version` class for semantic versioning
  - Version info in `/help` and `/bot_status` commands
- **SSL Configuration**: Configurable SSL verification for self-signed certificates
  - `verify_ssl` option in config (default: true)

### Changed
- **Documentation**: Fully translated README.md to English
- **Directory Structure**: Moved all code to `src/` with PSR-4 autoloading
- **Autoloading**: Switched from manual `require_once` to Composer autoloader
- **Translation Placeholders**: Corrected format from `:var` to `%var%` (Symfony standard)

### Fixed
- Missing `usage_network_summary` translation key
- Incorrect placeholder format in translation strings
- SSL certificate validation now configurable

### Removed
- Legacy migration script `migrate_v1_to_v2.php`
- Manual `require_once` statements (replaced with Composer autoload)

## [2.0.0]

### Added
- Complete codebase refactoring with modern PHP standards
- PSR-4 autoloading with Composer
- Dependency Injection architecture
- Alert tracking system with SQLite
- Advanced security features (rate limiting, role-based access)
- Command dispatcher pattern
- Structured logging system
- Comprehensive command set for device and network management

### Changed
- Migrated to object-oriented architecture
- Namespaced all classes under `LibreBot\`
- Separated commands into dedicated classes

---

## Version Format

- **MAJOR**: Incompatible API changes
- **MINOR**: New functionality (backward-compatible)
- **PATCH**: Bug fixes (backward-compatible)
