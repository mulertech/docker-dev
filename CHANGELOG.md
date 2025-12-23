# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/SemVer).

## [Unreleased]

### Added
- **Composer Command**: New `composer` command to run Composer commands inside Docker container
  - Execute any Composer command (install, update, require, remove, etc.)
  - Follows the same Docker lifecycle management as other commands
  - Example: `./vendor/bin/mtdocker composer require vendor/package`

## [2.0.0] - 2025-10-31

### Added
- **Object-Oriented Architecture**: Complete refactoring from procedural to clean OO design
- **Custom Arguments Support**: All commands now accept additional arguments (e.g., `phpstan --generate-baseline`, `cs-fixer --dry-run`)
- **Modular Command System**: Extensible architecture for adding new commands
- **CommandInterface and BaseCommand**: Standardized command structure
- **CommandRegistry**: Centralized command management and routing
- **Architecture Documentation**: Added detailed architecture section in README

### Changed
- **Application Class**: Refactored main application logic with switch-case instead of elseif chains
- **Command Names**: Renamed `TestCommand` to `PhpunitCommand` for future extensibility
- **Error Handling**: Improved GID conflict resolution in Dockerfiles
- **Permission Management**: Enhanced UID/GID handling for better file permissions
- **Code Organization**: Moved from single procedural file to organized class structure

### Fixed
- **GID Conflicts**: Resolved Docker build failures when host GID conflicts with container system groups
- **Project Directory Detection**: Fixed `.mtdocker` creation in wrong location (vendor/ instead of project root)
- **Dockerfile Logic**: Improved user creation logic with fallback mechanisms

### Technical Improvements
- **Separation of Concerns**: Clear separation between Composer analysis, Docker operations, and Symfony configurations
- **Testability**: Classes can now be unit tested independently
- **Maintainability**: Easier to add new commands and modify existing functionality
- **Type Safety**: Better use of interfaces and abstract classes

## [1.0.0] - 2025-08-26

### Added
- **Initial Release**: Complete Docker-based development environment package
- **Multiple Templates**: Support for Apache, MySQL, PostgreSQL, and Symfony environments
- **Auto-Detection**: Smart project type detection based on composer.json
- **Integrated Tools**: PHPUnit, PHPStan, and PHP-CS-Fixer integration
- **Symfony Support**: Automatic Doctrine and Mailer configuration
- **Port Management**: Deterministic port generation to avoid conflicts
- **Environment Management**: Complete Docker Compose setup and management

### Features
- **Template System**: apache-simple, apache-mysql, symfony, apache-html
- **Zero-Configuration**: Auto-initialization when running commands
- **Git Integration**: Automatic .gitignore management
- **IDE Support**: PHPStorm configuration helpers
- **Database Initialization**: SQL file support for database setup
- **Container Lifecycle**: Smart start/stop based on command needs

### Infrastructure
- **Docker Integration**: Full Docker Compose environment management
- **User Permissions**: Proper UID/GID mapping for file permissions
- **Port Conflict Resolution**: Deterministic port assignment
- **Template Auto-Detection**: composer.json analysis for optimal template selection