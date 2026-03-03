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

- **`Application`** — CLI dispatcher. Routes commands (`test`, `up`, `down`, `init`, `modules`, `symfony`, `composer`, `phpstan`, `cs-fixer`, `all`, `ps`, `name`, `link`) to Docker or CommandRegistry.
- **`Composer`** — Reads the consumer project's `composer.json` to detect PHP version, project type (Symfony, database-needed, AI/RAG packages), and project root directory.
- **`Docker`** — Manages Docker Compose lifecycle (`up`, `down`, `ps`), module initialization, port generation, container naming, and `.gitignore` management. Uses multi-file compose via `docker compose -f ... -f ...` with modules from `templates/modules/`.
- **`ModuleResolver`** — Encapsulates module detection logic. Uses `Composer` analysis to determine which modules are needed, resolves Dockerfiles and shared files to copy.
- **`Symfony`** — Applies Symfony-specific config: patches `config/packages/doctrine.yaml` and `config/packages/mailer.yaml` to use Docker environment variables and file-based secrets.

### Command System (`src/Command/`)

- **`CommandInterface`** — Contract: `getName()`, `getDefaultArgs()`, `execute()`, `requiresDocker()`.
- **`BaseCommand`** — Abstract. Handles Docker lifecycle around command execution: starts container if not running, runs command, stops container if it wasn't running before.
- **`CommandRegistry`** — Registers and dispatches to: `PhpunitCommand` (key: `test`), `PhpStanCommand` (key: `phpstan`), `CsFixerCommand` (key: `cs-fixer`), `ComposerCommand` (key: `composer`), `SymfonyCommand` (key: `symfony`).

### Module System (`templates/`)

The system uses composable modules instead of monolithic templates. Each module is a `compose.*.yml` file in `templates/modules/` that defines a single service or overlay. Modules are combined via Docker Compose multi-file merge (`docker compose -f ... -f ...`).

**Available modules:**
| Module | Description |
|--------|-------------|
| `frankenphp` | **Default.** FrankenPHP (Caddy) base (defines network). Mounts to `/app`. |
| `apache-php` | Alternative Apache + PHP base (defines network). Mounts to `/var/www/html`. |
| `apache-html` | Static Apache (no PHP, defines network) |
| `symfony` | Symfony overlay (php.ini, Caddyfile/apache.conf, mailer secret) |
| `postgres` | PostgreSQL 16 |
| `mysql` | MySQL 8 |
| `pgvector` | PostgreSQL 17 + pgvector extension |
| `redis` | Redis 7 |
| `mailpit` | MailPit (SMTP + web UI) |
| `adminer` | Adminer (DB web UI) |
| `ollama` | Ollama AI |

**Shared files** in `templates/shared/` are copied into `.mtdocker/` during init (Dockerfiles, Caddyfile, configs, SQL scripts, secrets).

**Module detection** via `ModuleResolver::detectModules()`:
- Symfony project → `frankenphp, symfony, postgres, redis, mailpit, adminer`
- Symfony + AI packages → `frankenphp, symfony, pgvector, ollama, redis, mailpit, adminer`
- DB needed (ext-pdo) → `frankenphp, postgres, adminer`
- PHP project → `frankenphp`
- No PHP → `apache-html`

**Init with explicit modules:** `mtdocker init frankenphp,postgres,adminer`

**Generated files in `.mtdocker/`:**
- `.env` — Generated variables (ports, paths, container names)
- `modules.json` — Active module list
- `php/Dockerfile` — Copied from shared/ based on modules
- Additional files (configs, secrets, SQL, adminer) as needed

**Key design:** Compose files stay in the package (referenced via `${MTDOCKER_PATH}` and `${PROJECT_PATH}` absolute paths in `.env`). Only build context files are copied to `.mtdocker/`.

## Code Conventions

- PHP 8.0+, no comments (self-documenting code)
- `DIRECTORY_SEPARATOR` for cross-platform paths
- `escapeshellarg()` for user input in shell commands, `passthru()` for interactive commands
- No try-catch unless necessary; prefer early returns
- Native PHP string functions (`str_contains`, `str_replace`) over regex where possible
