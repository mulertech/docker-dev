
# MulerTech Docker-dev

___
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mulertech/docker-dev.svg?style=flat-square)](https://packagist.org/packages/mulertech/docker-dev)
[![Total Downloads](https://img.shields.io/packagist/dt/mulertech/docker-dev.svg?style=flat-square)](https://packagist.org/packages/mulertech/docker-dev)
___


The **MulerTech Docker-dev** package provides complete Docker-based development environments for web projects with multiple templates (Apache, MySQL/PostgreSQL, Symfony) and includes integrated testing capabilities.

## Description

This package simplifies web development by providing pre-configured Docker environments for different project types. It offers ready-to-use templates for various development stacks and includes integrated testing tools (PHPUnit, PHPStan, PHP-CS-Fixer) that work seamlessly within the containerized environment.

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

Since this template doesn't use PHP or Composer, you have two options:

#### Option 1: Quick Setup with Install Script (Recommended)

```sh
# Download and run the installation script in the project directory
curl -sSL https://raw.githubusercontent.com/mulertech/docker-dev/main/install-apache-html.sh | bash
```

This script will:
- Download all necessary Docker files
- Auto-configure environment variables (USER_ID, GROUP_ID, unique ports)
- Create a sample `index.html` file (if none exists)
- Provide ready-to-use Docker environment

#### Option 2: Manual Setup

1. Download the apache-html template files from [GitHub](https://github.com/mulertech/docker-dev/tree/main/templates/apache-html)
2. Copy the files to your project root:
   - `Dockerfile`
   - `compose.yml` 
   - `.env.example` (rename to `.env`)
3. Run the Docker environment:
   ```sh
   docker-compose up -d
   ```

## Usage

### Development Environment Templates

The primary feature of this package is to quickly set up complete development environments. Initialize your project with:

```sh
# Auto-detect and initialize the best template for your project
./vendor/bin/mtdocker init

# Or choose a specific template
./vendor/bin/mtdocker init symfony
./vendor/bin/mtdocker init symfony-pgvector-ollama
./vendor/bin/mtdocker init apache-mysql  
./vendor/bin/mtdocker init apache-simple
./vendor/bin/mtdocker init apache-html
```

This creates a complete development environment with web server, database (if needed), and all necessary services ready to use.

**🚀 Zero-Configuration Mode:**
You can also just run any command directly and the environment will be auto-initialized:

```sh
# These commands automatically set up your environment if needed
./vendor/bin/mtdocker up -d
./vendor/bin/mtdocker test
./vendor/bin/mtdocker down
```

No manual setup required - the system detects your project type and creates the appropriate environment automatically.

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

### Available Templates

**Smart template auto-detection:**
1. **Symfony projects with AI/RAG** → `symfony-pgvector-ollama` template (detects AI packages: `pgvector`, `openai`, `anthropic`, `langchain`, `chromadb`, `yethee/tiktoken`)
2. **Symfony projects** → `symfony` template (detects `symfony/framework-bundle`, `symfony/symfony`, or `symfony/kernel`)
3. **Database projects** → `apache-mysql` template (detects `ext-pdo` requirement) 
4. **Simple projects** → `apache-simple` template (fallback)

**Available templates:**
- `apache-simple`: Basic Apache + PHP environment for simple web projects
- `apache-mysql`: Apache + PHP + MySQL environment for database-driven applications
- `apache-html`: Pure Apache HTTP server for static HTML/CSS/JS projects (no PHP) - [Download template files](https://github.com/mulertech/docker-dev/tree/main/templates/apache-html)
- `symfony`: Complete Symfony development environment with Apache, PostgreSQL 15, Adminer, Redis, and MailPit (automatically configures Doctrine for Docker environment)
- `symfony-pgvector-ollama`: Advanced Symfony environment for AI/RAG projects with Apache, PostgreSQL 17 + pgvector extension, Ollama (local LLM), Adminer, Redis, and MailPit

**Template initialization process:**
- Creates a `.mtdocker/` directory in your project root
- Copies all necessary Docker configuration files
- Creates a `.env` file with auto-detected system settings (USER_ID, GROUP_ID, PHP version)
- Generates deterministic ports based on project name to avoid conflicts
- **Automatically adds `.mtdocker/` to `.gitignore`** (best practice)
- **For Symfony projects**: Interactive template selection with smart defaults based on detected AI/RAG packages
- **Automatically configures Symfony projects** with Doctrine settings for PostgreSQL into `doctrine.yaml`
- **Automatically configures Symfony Mailer** to use MailPit into `.env`
- Provides a complete development environment ready to use

### Database Initialization

For templates with databases (`apache-mysql` with MySQL, and `symfony` with PostgreSQL), you can easily initialize your database with custom SQL files:

```sh
# 1. Copy your SQL files to the sql directory
cp my-backup.sql .mtdocker/sql/02-my-data.sql
cp schema.sql .mtdocker/sql/01-schema.sql

# 2. Restart the environment to apply changes
./vendor/bin/mtdocker down
./vendor/bin/mtdocker up -d
```

**File execution order:**
- `01-init-user.sql` (system - for MySQL creates user with network permissions, for PostgreSQL provides additional setup if needed)
- Your SQL files in alphabetical order (e.g., `01-schema.sql`, `02-data.sql`)
- Supports `.sql`, `.sql.gz`, and `.sh` files

## IDE Integration

### PHPStorm Configuration

Configure PHPStorm to work with your Docker development environment:

**PHP Interpreter Setup:**
1. Open PHPStorm settings → `PHP`
2. Click `...` next to `CLI Interpreter` field
3. Add new interpreter: `From Docker, Vagrant, VM, WSL, Remote...`
4. Configure Docker Compose interpreter:
   - Server: `Docker` (create new if needed)
   - Configuration files: `./.mtdocker/compose.yml`
   - Service: `apache` or `php`
   - Environment variables: `COMPOSE_PROJECT_NAME=<project name>` (get with `./vendor/bin/mtdocker name`)

**PHPUnit Integration:**
1. Go to `PHP` → `Test Frameworks`
2. Add `PHPUnit by Remote Interpreter`
3. Select your Docker interpreter
4. Path to script: `/var/www/html/vendor/autoload.php`
5. Default configuration file: `/var/www/html/phpunit.dist.xml` (needed for Symfony projects)

## Architecture

### Core Classes
- **`Composer`**: Project analysis (PHP version detection, database requirements, Symfony detection)
- **`Docker`**: Template management, port generation, Docker operations
- **`Symfony`**: Symfony-specific configurations (Doctrine, Mailer)
- **`Application`**: Main orchestrator handling CLI commands

### Command System
- **`CommandInterface`**: Contract for all commands
- **`BaseCommand`**: Abstract base with Docker lifecycle management
- **Specific Commands**: `PhpunitCommand`, `PhpStanCommand`, `CsFixerCommand`, `ComposerCommand`, `SymfonyCommand`
- **`CommandRegistry`**: Command routing and management

### Key Features
- **Custom Arguments**: All commands support additional arguments (e.g., `./vendor/bin/mtdocker phpstan --generate-baseline`)
- **Auto-initialization**: Environment setup happens automatically when needed
- **Smart Detection**: Project type detection for optimal template selection
- **Permission Handling**: Proper UID/GID management for file permissions