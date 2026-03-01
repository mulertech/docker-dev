# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**MulerTech Docker-dev** is a Composer package (`mulertech/docker-dev`) that provides pre-configured Docker environments for PHP/Symfony web projects. It is installed as a dev dependency into consumer projects and exposes a `mtdocker` CLI binary.

## Commands

All commands are run via the `mtdocker` binary. Within this repository itself (for development/testing of the package), use:

```sh
# Run tests
./vendor/bin/mtdocker test

# Run a single test
./vendor/bin/mtdocker test --filter=TestClassName::testMethodName

# Run tests with coverage (HTML report in .phpunit.cache/coverage/)
./vendor/bin/mtdocker test-coverage

# Run tests with text coverage (AI-readable)
./vendor/bin/mtdocker test-coverage-text

# Run PHPStan static analysis
./vendor/bin/mtdocker phpstan

# Run PHP CS Fixer
./vendor/bin/mtdocker cs-fixer

# Run all checks (cs-fixer, test, phpstan)
./vendor/bin/mtdocker all
```

## Architecture

### Entry Point
`mtdocker` (bin file at root) → `Application::run()` dispatches CLI args via a `switch` statement to handler methods.

### Core Classes (`src/`)

- **`Application`** — CLI dispatcher. Routes commands (`test`, `up`, `down`, `init`, `symfony`, `composer`, `phpstan`, `cs-fixer`, `all`, `ps`, `name`, `link`) to Docker or CommandRegistry.
- **`Composer`** — Reads the consumer project's `composer.json` to detect PHP version, project type (Symfony, database-needed, AI/RAG packages), and project root directory.
- **`Docker`** — Manages Docker Compose lifecycle (`up`, `down`, `ps`), template detection/initialization, port generation, container naming, and `.gitignore` management. All Docker commands target `.mtdocker/compose.yml` in the consumer project.
- **`Symfony`** — Applies Symfony-specific config: patches `config/packages/doctrine.yaml` and `config/packages/mailer.yaml` to use Docker environment variables and file-based secrets.

### Command System (`src/Command/`)

- **`CommandInterface`** — Contract: `getName()`, `getDefaultArgs()`, `execute()`, `requiresDocker()`.
- **`BaseCommand`** — Abstract. Handles Docker lifecycle around command execution: starts container if not running, runs command, stops container if it wasn't running before.
- **`CommandRegistry`** — Registers and dispatches to: `PhpunitCommand` (key: `test`), `PhpStanCommand` (key: `phpstan`), `CsFixerCommand` (key: `cs-fixer`), `ComposerCommand` (key: `composer`), `SymfonyCommand` (key: `symfony`).

### Template System (`templates/`)

Five templates copied into `.mtdocker/` in the consumer project:
- `apache-simple` — Apache + PHP
- `apache-mysql` — Apache + PHP + MySQL
- `apache-html` — Static Apache only (no PHP)
- `symfony` — Apache + PostgreSQL 15 + Adminer + Redis + MailPit
- `symfony-pgvector-ollama` — Apache + PostgreSQL 17 + pgvector + Ollama + Adminer + Redis + MailPit

Template selection is auto-detected via `Composer` class heuristics (priority: Symfony+AI > Symfony > DB > simple).

During `init`, `.env.example` is processed to substitute real `USER_ID`, `GROUP_ID`, PHP image, container names, and deterministic port numbers (generated via MD5 hash of project name, mapped to range 1024–49151).

## Code Conventions

- PHP 8.0+, no comments (self-documenting code)
- `DIRECTORY_SEPARATOR` for cross-platform paths
- `escapeshellarg()` for user input in shell commands, `passthru()` for interactive commands
- No try-catch unless necessary; prefer early returns
- Native PHP string functions (`str_contains`, `str_replace`) over regex where possible
