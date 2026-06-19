
# MulerTech Docker-dev

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mulertech/docker-dev.svg?style=flat-square)](https://packagist.org/packages/mulertech/docker-dev)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/mulertech/docker-dev/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/mulertech/docker-dev/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mulertech/docker-dev.svg?style=flat-square)](https://packagist.org/packages/mulertech/docker-dev)


The **MulerTech Docker-dev** package provides complete Docker-based development environments for web projects with a modular, composable architecture (Apache, MySQL/PostgreSQL/PostGIS, Symfony, Redis, MailPit, Ollama, Valhalla...) and includes integrated testing capabilities.

## Description

This package simplifies web development by providing pre-configured Docker environments for different project types. It uses a **modular system** where each service (database, cache, mail...) is an independent module that can be freely combined. It includes integrated testing tools (PHPUnit, PHPStan, PHP-CS-Fixer) that work seamlessly within the containerized environment.

## Prerequisites

### For PHP Projects
- Docker
- PHP
- Composer

### For Static HTML/CSS/JS Projects
- Docker only

## Installation

### For PHP Projects (Symfony, Apache-MySQL, Apache-Simple)

1. Include the package as a dev dependency with Composer :

    ```sh
    composer require --dev mulertech/docker-dev
    ```

### For Static HTML/CSS/JS Projects (Apache-HTML)

Since static projects don't use PHP or Composer, the modular system (`mtdocker`) is not available. A standalone template is provided with two setup options:

#### Option 1: Quick Setup with Install Script (Recommended)

```sh
# Download and run the installation script in the project directory
curl -sSL https://raw.githubusercontent.com/mulertech/docker-dev/main/install-apache-html.sh | bash
```

This script will:
- Download all necessary Docker files (`Dockerfile`, `compose.yml`, `.env`)
- Auto-configure environment variables (USER_ID, GROUP_ID, unique ports)
- Create a sample `index.html` file (if none exists)
- Provide ready-to-use Docker environment

#### Option 2: Manual Setup

1. Download the standalone template files from [`templates/apache-html/`](https://github.com/mulertech/docker-dev/tree/main/templates/apache-html)
2. Copy the 3 files to your project root:
   - `Dockerfile`
   - `compose.yml`
   - `.env.example` (rename to `.env` and adjust USER_ID, GROUP_ID, APACHE_PORT)
3. Run the Docker environment:
   ```sh
   docker compose up -d
   ```

## PHP Sandbox

A lightweight PHP sandbox mode for quick experimentation, learning, or exams. Uses a minimal `php:cli-alpine` Docker image (~30 MB) that starts in about 1 second.

### Getting Started with VS Code

```sh
git clone https://github.com/mulertech/docker-dev.git && cd docker-dev && ./mtdocker sandbox && code .
```

### Getting Started with PHPStorm

```sh
git clone https://github.com/mulertech/docker-dev.git && cd docker-dev && ./mtdocker sandbox && phpstorm .
```

This single command clones the repository, initializes the sandbox environment, executes `sandbox.php` in a Docker container, and opens your IDE. A `sandbox.php` file is created at the project root with `dump()` and `dd()` helper functions ready to use.

Edit `sandbox.php`, then run:

```sh
./run
```

### Available Helper Functions

- `dump($var)` — Pretty-prints a variable with type and color formatting (ANSI for CLI, HTML for web).
- `dd($var)` — Dump and die.

## Usage

### Development Environment (Modular System)

**🚀 Zero-Configuration Mode:** No setup required. Just run any command and the environment is automatically created based on your `composer.json`:

```sh
./vendor/bin/mtdocker up -d
./vendor/bin/mtdocker test
./vendor/bin/mtdocker phpstan
```

The system detects your project type (Symfony, database, AI/RAG packages...) and activates the appropriate modules automatically.

**Manual initialization:** If you want to control which modules are activated, use `init` with a comma-separated list:

```sh
# Auto-detect modules
./vendor/bin/mtdocker init

# Or choose specific modules
./vendor/bin/mtdocker init frankenphp,symfony,postgres,redis,mailpit,adminer
./vendor/bin/mtdocker init frankenphp,postgres,adminer
./vendor/bin/mtdocker init frankenphp

# View active modules
./vendor/bin/mtdocker modules
```

### Managing Your Development Environment

```sh
# Start your development environment
./vendor/bin/mtdocker up -d

# Stop your environment
./vendor/bin/mtdocker down

# Check environment status
./vendor/bin/mtdocker ps
```

### Testing and Code Quality Tools

Integrated testing tools work seamlessly within your development environment:

#### Running the tests

To run the tests, use the following command:

```sh
./vendor/bin/mtdocker test [arguments...]
```

You can pass any additional arguments to PHPUnit:

```sh
# Run specific test class
./vendor/bin/mtdocker test --filter=MyTestClass

# Run with verbose output
./vendor/bin/mtdocker test --verbose

# Run specific test method
./vendor/bin/mtdocker test --filter=MyTestClass::testMyMethod
```

To run the tests with code coverage, use the following command:

```sh
./vendor/bin/mtdocker test-coverage
```

The code coverage report will be generated in the `./.phpunit.cache/coverage` folder.

To get a plain text coverage report with code coverage (AI-optimized output):

```sh
./vendor/bin/mtdocker test-coverage-ai
```

The coverage report will be displayed directly in the terminal as plain text without colors, progress bar, or skipped/incomplete test noise.

These commands will:
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run the PHPUnit tests in the container with all provided arguments.
- Stop the container if it was not running before the tests were executed.

### Running phpstan

To run phpstan, use the following command:

```sh
./vendor/bin/mtdocker phpstan [arguments...]
```

You can pass any additional arguments to phpstan:

```sh
# Run with custom memory limit
./vendor/bin/mtdocker phpstan analyse --memory-limit=2G

# Generate baseline
./vendor/bin/mtdocker phpstan analyse --generate-baseline
```

This command will:
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run phpstan in the container with all provided arguments.
- Stop the container if it was not running before phpstan was executed.

### Running php-cs-fixer

To run php-cs-fixer, use the following command:

```sh
./vendor/bin/mtdocker cs-fixer [arguments...]
```

You can pass any additional arguments to php-cs-fixer:

```sh
# Fix specific files
./vendor/bin/mtdocker cs-fixer fix src/MyClass.php

# Check only (dry-run)
./vendor/bin/mtdocker cs-fixer fix --dry-run
```

This command will:
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run php-cs-fixer in the container with all provided arguments.
- Stop the container if it was not running before php-cs-fixer was executed.

### AI Agent Commands

A set of commands optimized for use by AI agents: minimal output, no ANSI colors, no progress bars, machine-readable formats where applicable.

#### test-ai

Runs PHPUnit showing only failures, errors, deprecations, notices and warnings — suppresses progress bar, skipped and incomplete tests:

```sh
./vendor/bin/mtdocker test-ai [arguments...]
```

Additional PHPUnit arguments are supported:

```sh
# Run a specific test class
./vendor/bin/mtdocker test-ai --filter=MyTestClass

# Run a specific test method
./vendor/bin/mtdocker test-ai --filter=MyTestClass::testMyMethod
```

#### test-coverage-ai

Runs PHPUnit with a plain text coverage report, same noise suppression as `test-ai`:

```sh
./vendor/bin/mtdocker test-coverage-ai
```

#### phpstan-ai

Runs PHPStan with JSON output and no progress bar — directly parsable by an agent:

```sh
./vendor/bin/mtdocker phpstan-ai [arguments...]
```

#### cs-fixer-ai

Runs PHP CS Fixer silently (applies fixes, JSON report, no ANSI, no progress bar):

```sh
./vendor/bin/mtdocker cs-fixer-ai [arguments...]
```

#### all-ai

Runs `cs-fixer-ai`, `test-ai` and `phpstan-ai` in sequence:

```sh
./vendor/bin/mtdocker all-ai
```

#### ps-ai

Returns Docker Compose container status as JSON:

```sh
./vendor/bin/mtdocker ps-ai
```

### Composer Commands

You can run Composer commands directly in the Docker container:

```sh
# Install dependencies
./vendor/bin/mtdocker composer install

# Update dependencies
./vendor/bin/mtdocker composer update

# Require a new package
./vendor/bin/mtdocker composer require vendor/package

# Remove a package
./vendor/bin/mtdocker composer remove vendor/package

# Any other Composer command
./vendor/bin/mtdocker composer [command] [arguments...]
```

This command will:
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run `composer [command]` in the container with all provided arguments.
- Stop the container if it was not running before the command was executed.

### Symfony Console Commands (Symfony projects only)

For Symfony projects, you can run console commands directly in the container:

```sh
# Create a new entity
./vendor/bin/mtdocker symfony make:entity MyEntity

# Run database migrations
./vendor/bin/mtdocker symfony doctrine:migrations:migrate

# Clear cache
./vendor/bin/mtdocker symfony cache:clear

# Any other Symfony console command
./vendor/bin/mtdocker symfony [command] [arguments...]
```

This command will:
- Check that your project is a Symfony project.
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run `php bin/console [command]` in the container with all provided arguments.
- Stop the container if it was not running before the command was executed.

### Running php-cs-fixer, phpunit and phpstan

To run php-cs-fixer, phpunit and phpstan, use the following command:

```sh
./vendor/bin/mtdocker all
```

This command will:
- Check if the Docker container is running.
- If the container is not running, it will be started.
- Run php-cs-fixer, phpunit and phpstan in the container.
- Stop the container if it was not running before the checks were executed.

For an AI-optimized version of this full pipeline, use:

```sh
./vendor/bin/mtdocker all-ai
```

### Advanced Configuration

#### Getting the project name

To get the project name used for Docker Compose (useful for PHPStorm configuration), use the following command:

```sh
./vendor/bin/mtdocker name
```

This command will output the project name that should be used in the `COMPOSE_PROJECT_NAME` environment variable when configuring PHPStorm.

#### Getting the application link

To get the clickable link to access your application, use the following command:

```sh
./vendor/bin/mtdocker link
```

This command will display a clickable link to your application (e.g., http://localhost:8080).

### Available Modules

Each module is an independent Docker Compose file that can be freely combined with others.

| Module | Services | Description |
|--------|----------|-------------|
| `frankenphp` | FrankenPHP (Caddy) | **Default.** Modern PHP app server built on Caddy. Defines the shared network. |
| `apache-php` | Apache + PHP | Alternative base web server with Apache + mod_php. Defines the shared network. |
| `apache-html` | Apache | Static web server without PHP (httpd:2.4-alpine). Defines the shared network. |
| `symfony` | *(overlay)* | Adds Symfony configuration: php.ini, Caddyfile/apache.conf, mailer secret. |
| `postgres` | PostgreSQL 16 | PostgreSQL database with volume persistence and SQL init scripts. |
| `mysql` | MySQL 8 | MySQL database with volume persistence, UTF-8 charset, and SQL init scripts. |
| `pgvector` | PostgreSQL 17 + pgvector | PostgreSQL with vector embeddings extension for AI/RAG projects. |
| `postgis` | PostGIS 16 (PostgreSQL 16) | PostgreSQL with the PostGIS spatial extension enabled (main + test databases) for geospatial projects. With FrankenPHP + Symfony, the generated Caddyfile also serves `public/tiles/*` (PMTiles, glyphs) with a long immutable cache. |
| `redis` | Redis 7 | Redis cache server (Alpine). |
| `mailpit` | MailPit | Local mail server with SMTP capture and web interface. |
| `adminer` | Adminer | Database web administration interface with auto-login and dark theme. |
| `ollama` | Ollama | Local LLM server for AI/RAG projects. |
| `gotenberg` | Gotenberg 8 | Stateless PDF/document conversion API (runs as a separate container, reached by the web service via `GOTENBERG_URL`). |
| `valhalla` | Valhalla (gis-ops) | Self-hosted routing engine for geospatial projects, reached via `VALHALLA_URL`. **Opt-in only** (see below) — serves a pre-built tile-pack from `var/dev/valhalla/custom_files/`. |
| `sandbox` | PHP CLI (Alpine) | **Standalone.** Lightweight PHP sandbox for quick experimentation. Cannot be combined with other modules. |

#### Smart auto-detection

When running `./vendor/bin/mtdocker init` without arguments, modules are automatically selected based on your `composer.json`:

| Detection criteria | Modules activated |
|--------------------|-------------------|
| `symfony/framework-bundle`, `symfony/symfony`, or `symfony/kernel` detected **+ AI packages** (`pgvector`, `openai`, `anthropic`, `langchain`, `chromadb`, `yethee/tiktoken`) | `frankenphp`, `symfony`, `pgvector`, `ollama`, `redis`, `mailpit`, `adminer` |
| `symfony/framework-bundle`, `symfony/symfony`, or `symfony/kernel` detected **+ spatial packages** (`longitude-one/doctrine-spatial`, `jsor/doctrine-postgis`, `postgis`) | `frankenphp`, `symfony`, `postgis`, `redis`, `mailpit`, `adminer` |
| `symfony/framework-bundle`, `symfony/symfony`, or `symfony/kernel` detected | `frankenphp`, `symfony`, `postgres`, `redis`, `mailpit`, `adminer` |
| `ext-pdo` requirement detected | `frankenphp`, `postgres`, `adminer` |
| PHP version detected (any PHP project) | `frankenphp` |
| No PHP detected | `apache-html` |

For Symfony projects, the packages in `composer.json` determine whether `pgvector` + `ollama` (AI/RAG), `postgis` (spatial), or standard `postgres` is activated. Independently of the database choice, the `gotenberg` module is added to the Symfony stack whenever `sensiolabs/gotenberg-bundle` is detected. To use Apache instead of FrankenPHP, pass the modules explicitly (e.g., `mtdocker init apache-php,symfony,postgres,redis,mailpit,adminer`).

#### Explicit module opt-in (`extra.mtdocker.modules`)

Some modules back a service that has **no detectable composer dependency** (e.g. a self-hosted engine consumed through a hand-written client). A project requests such a module explicitly in its `composer.json`, and it is merged into auto-detection — without affecting any other project:

```json
{
    "extra": {
        "mtdocker": {
            "modules": ["valhalla"]
        }
    }
}
```

The listed modules are appended (deduplicated) to whatever auto-detection resolves. This is the supported way to enable the `valhalla` module.

#### The `valhalla` routing module

Enabled via the opt-in above, the `valhalla` module starts a [gis-ops/docker-valhalla](https://github.com/gis-ops/docker-valhalla) container on the shared network and exposes `VALHALLA_URL=http://valhalla:8002` to the web service.

- **Pre-built tile-pack required.** Valhalla tiles are built outside Docker and dropped into `var/dev/valhalla/custom_files/` (configurable via `VALHALLA_TILES_PATH`). The mount is read-write because the gis-ops entrypoint writes hashes / extracts tiles there on startup. `var/dev/` is excluded from the image build context, so multi-gigabyte tile-packs never bloat the build.
- **Graceful pre-flight.** On `up`, the presence of the tile-pack is checked via a sentinel file (`VALHALLA_TILES_SENTINEL`, default `valhalla.json`). If the tiles are missing, `mtdocker` prints the build steps and **starts every other container without Valhalla** — the app degrades cleanly (routing returns 503) instead of crash-looping a tiles-less container.

#### Module combinations examples

```sh
# Symfony standard stack (FrankenPHP)
./vendor/bin/mtdocker init frankenphp,symfony,postgres,redis,mailpit,adminer

# Symfony AI/RAG stack
./vendor/bin/mtdocker init frankenphp,symfony,pgvector,ollama,redis,mailpit,adminer

# Symfony spatial stack (PostGIS)
./vendor/bin/mtdocker init frankenphp,symfony,postgis,redis,mailpit,adminer

# Symfony spatial stack with self-hosted routing (PostGIS + Valhalla)
./vendor/bin/mtdocker init frankenphp,symfony,postgis,valhalla,redis,mailpit,adminer

# PHP + PostgreSQL + Adminer
./vendor/bin/mtdocker init frankenphp,postgres,adminer

# Minimal PHP only
./vendor/bin/mtdocker init frankenphp

# Symfony with Apache (instead of FrankenPHP)
./vendor/bin/mtdocker init apache-php,symfony,postgres,redis,mailpit,adminer

# PHP + MySQL with Apache
./vendor/bin/mtdocker init apache-php,mysql,adminer

# Static HTML site
./vendor/bin/mtdocker init apache-html
```

#### Initialization process

- Creates a `.mtdocker/` directory in your project root
- Copies necessary build files (Dockerfile, configs, secrets, SQL scripts)
- Creates a `.env` file with auto-detected system settings (USER_ID, GROUP_ID, PHP version, absolute paths)
- Saves the active module list in `.mtdocker/modules.json`
- Generates deterministic ports based on project name to avoid conflicts
- **Automatically adds `.mtdocker/` to `.gitignore`** (best practice)
- **For Symfony projects**: Automatically configures Doctrine settings for PostgreSQL into `doctrine.yaml` (including `when@test` override for PHPStorm compatibility, and `server_version: '%env(default::DATABASE_SERVER_VERSION)%'` driven by the `DATABASE_SERVER_VERSION` variable so the same key works locally and in production)
- **For Symfony projects**: Automatically configures Mailer to use MailPit into `mailer.yaml` (including `when@test` override for PHPStorm compatibility)
- **For Symfony projects with database**: Generates `.env.test.local` with database connection settings for PHPStorm (`host.docker.internal`, dynamic port, credentials)
- Provides a complete development environment ready to use

#### Migration from legacy templates

If your project was initialized with a previous version (using monolithic templates with `compose.yml`), running any `mtdocker` command will detect the legacy configuration and propose a re-initialization with the new module system.

### Database Initialization

For configurations with database modules (`mysql`, `postgres`, `pgvector`, or `postgis`), you can easily initialize your database with custom SQL files:

```sh
# 1. Copy your SQL files to the sql directory
cp my-backup.sql .mtdocker/sql/02-my-data.sql
cp schema.sql .mtdocker/sql/01-schema.sql

# 2. Restart the environment to apply changes
./vendor/bin/mtdocker down
./vendor/bin/mtdocker up -d
```

**File execution order:**
- `01-init-user.sql` (system - for MySQL creates user with network permissions, for PostgreSQL provides additional setup if needed; the `postgis` module uses a dedicated variant that enables the PostGIS extension on the main and test databases)
- Your SQL files in alphabetical order (e.g., `01-schema.sql`, `02-data.sql`)
- Supports `.sql`, `.sql.gz`, and `.sh` files

## IDE Integration

### PHPStorm Configuration

Configure PHPStorm to work with your Docker development environment:

**PHP Interpreter Setup:**
1. Open PHPStorm settings → `PHP`
2. Click `...` next to `CLI Interpreter` field
3. Add new interpreter: `From Docker, Vagrant, VM, WSL, Remote...`
4. Configure Docker interpreter:
   - Server: `Docker` (create new if needed)
   - Image name: `docker-<project name>-<php version with - instead of .>-web:latest` (e.g., `docker-myproject-8-2-web:latest`)
   - PHP Interpreter path: `php`
5. Go to `PHP` → `Docker container` → click on the folder icon
6. Add a volume binding:
   - **Host path**: your project root (e.g., `/Users/you/projects/myproject`)
   - **Container path**: `/app` (for FrankenPHP) or `/var/www/html` (for Apache-PHP)

**PHPUnit Integration:**
1. Go to `PHP` → `Test Frameworks`
2. Add `PHPUnit by Remote Interpreter`
3. Select your Docker interpreter
4. Use Composer autoloader:
   - Path to script: `/app/vendor/autoload.php` (FrankenPHP) or `/var/www/html/vendor/autoload.php` (Apache-PHP)
5. Default configuration file: `/app/phpunit.dist.xml` (FrankenPHP) or `/var/www/html/phpunit.dist.xml` (Apache-PHP)

> **Note for Symfony projects:** PHPStorm runs tests outside of Docker Compose, so environment variables and secrets from compose files are not available. The `mtdocker init` command automatically generates a `.env.test.local` file with the database connection settings (`DATABASE_HOST=host.docker.internal`, dynamic port, credentials) and configures `when@test` overrides in `doctrine.yaml` and `mailer.yaml` to use direct environment variables instead of file-based secrets. This file is regenerated on each `mtdocker init` to keep ports in sync.

## Architecture

### Core Classes
- **`Application`**: Main CLI dispatcher handling all commands
- **`Composer`**: Project analysis (PHP version detection, database requirements, Symfony detection, AI/RAG package detection, explicit module opt-in via `extra.mtdocker.modules`)
- **`Docker`**: Module initialization, Docker Compose lifecycle, port generation, container naming, pre-flight checks (e.g. graceful skip of `valhalla` when its tile-pack is missing)
- **`ModuleResolver`**: Module auto-detection logic based on `composer.json` analysis (merging explicit opt-in modules), file resolution for each module combination
- **`Symfony`**: Symfony-specific configurations (Doctrine, Mailer)

### Command System
- **`CommandInterface`**: Contract for all commands
- **`BaseCommand`**: Abstract base with Docker lifecycle management
- **Specific Commands**: `PhpunitCommand`, `PhpStanCommand`, `CsFixerCommand`, `ComposerCommand`, `SymfonyCommand`, `SandboxCommand`
- **`CommandRegistry`**: Command routing and management

### Module System
- **`templates/modules/`**: 15 independent Docker Compose files, one per module. They stay in the package and are referenced via absolute paths.
- **`templates/shared/`**: Build context files (Dockerfiles, configs, SQL scripts, secrets) copied into `.mtdocker/` during initialization.
- **Docker Compose merge**: Modules are combined via `docker compose -f ... -f ...`. The base module (`apache-php` or `apache-html`) is always loaded first, then overlays are applied in order.

### Key Features
- **Composable Modules**: Mix and match services freely instead of choosing from fixed templates
- **Custom Arguments**: All commands support additional arguments (e.g., `./vendor/bin/mtdocker phpstan --generate-baseline`)
- **Auto-initialization**: Environment setup happens automatically when needed
- **Smart Detection**: Project type detection for optimal module selection
- **Permission Handling**: Proper UID/GID management for file permissions
- **Legacy Migration**: Automatic detection and migration from old template-based configurations
- **AI Agent Commands**: Dedicated `*-ai` variants (`test-ai`, `phpstan-ai`, `cs-fixer-ai`, `all-ai`, `ps-ai`, `test-coverage-ai`) with minimal output, no ANSI colors, and machine-readable formats for seamless integration with AI agents