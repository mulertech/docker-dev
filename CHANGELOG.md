# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/SemVer).

## v3.8.0 - 2026-07-20

- Changed: the PostgreSQL major version is now driven by the existing `DATABASE_SERVER_VERSION` key alone. It was previously hardcoded to `16` in the compose template, with no way to override it from a project since module compose files are read straight from `vendor/`. Projects targeting another major (18 in production, for instance) now only change that one value.
- Added: `POSTGRES_IMAGE_TAG` and `POSTGRES_DATA_PATH`, both **computed by mtdocker** from `DATABASE_SERVER_VERSION` and injected into the `docker compose` call. They are not written to `.mtdocker/.env` and are not meant to be set by hand.
  - Only the major is kept for the image tag, so `16`, `16.0` and `16.4` all resolve to `postgres:16` and stay on the latest patch of that branch. Existing projects generated with `DATABASE_SERVER_VERSION=16.0` are therefore unaffected.
  - PostgreSQL 18+ images store data in a major-version subdirectory and **refuse to start** on the legacy mount point, so the mount is switched to `/var/lib/postgresql` from 18 onwards.
  
- Changed: generated `.env` now writes the major only (`DATABASE_SERVER_VERSION=16` instead of `16.0`). Doctrine accepts a bare major, and DBAL 4 resolves every version from 12 upwards to the same platform.
- Note: changing the major version requires removing the existing `postgres-data` volume, as the data directory is not compatible across majors.
- Known limitation, unchanged: `mtdocker down -v` does not forward `-v` to `docker compose`, so the volume must be removed with `docker volume rm <project>_postgres-data`.
- Unchanged: the `pgvector` and `postgis` modules keep their pinned images, which are not plain `postgres` tags.

## v3.7.0 - 2026-06-24

New `photon` geocoding module

- Added the `photon` module (`rtuszik/photon-docker`, [Photon](https://github.com/komoot/photon) by komoot), a self-hosted geocoder wired on `mtdocker-network`. It serves a pre-built search index from `var/dev/photon/data/` (`PHOTON_DATA_PATH`) and exposes `PHOTON_URL=http://photon:2322` to the web container, with `CONTAINER_NAME_PHOTON` and a generated `PHOTON_PORT`. Like `valhalla`, it is **opt-in only** via `composer.json` `extra.mtdocker.modules` (e.g. `["valhalla", "photon"]`) — there is no detectable composer dependency for a self-hosted geocoder reached through a hand-written client.
- The mount is read-write because the embedded search engine writes lock/state files on startup; runtime index updates are disabled (`UPDATE_STRATEGY=DISABLED`) so the container only serves the index dropped in. `var/dev/` remains excluded from the image build context, so the multi-gigabyte index never bloats the build.
- Graceful pre-flight: `mtdocker up` checks the index sentinel (`PHOTON_DATA_SENTINEL`, default `photon_data` — the subdirectory the prebuilt `.tar.bz2` extracts to); if missing, it warns with the steps and starts every other container without Photon (geocoding falls back) instead of looping an index-less container.

## v3.6.1 - 2026-06-19

Patch fix for the valhalla module introduced in v3.6.0.

- Fixed: the tile-pack volume (VALHALLA_TILES_PATH → /custom_files) is now mounted read-write instead of :ro. The gis-ops entrypoint writes hashes and extracts tiles into /custom_files on startup, so a read-only mount caused the container to exit immediately.
- Removed: the use_tiles_ignore_pbf=True environment variable from the Valhalla service.
- Docs: README.md and CHANGELOG.md updated to reflect the read-write mount (the tile-pack is still built outside Docker and var/dev/ remains excluded from the build context).

## v3.6.0 - 2026-06-19

New `valhalla` routing module + explicit module opt-in

- Added the `valhalla` module (`ghcr.io/gis-ops/docker-valhalla`), a self-hosted routing engine wired on `mtdocker-network`. It serves a pre-built tile-pack read-only from `var/dev/valhalla/custom_files/` (`VALHALLA_TILES_PATH`) and exposes `VALHALLA_URL=http://valhalla:8002` to the web container, with `CONTAINER_NAME_VALHALLA` and a generated `VALHALLA_PORT`.
- Explicit module opt-in: projects can request a bespoke module that has no detectable composer dependency via `composer.json` `extra.mtdocker.modules` (e.g. `["valhalla"]`). Merged into auto-detection without affecting other projects.
- Graceful pre-flight: `mtdocker up` checks the tile-pack sentinel (`VALHALLA_TILES_SENTINEL`, default `valhalla.json`); if missing, it warns with the build steps and starts every other container without Valhalla (the app degrades to 503 on routing) instead of looping a tiles-less container.
- The shared `.dockerignore` now excludes `var/dev/` so dev-only data (tile-packs, import files, often gigabytes) never enters the image build context.

## v3.5.0 - 2026-06-10

Caddyfile variant for geospatial (postgis) Symfony projects

- New `templates/shared/php/Caddyfile.symfony-postgis`, selected by `ModuleResolver` when the `frankenphp`, `symfony` and `postgis` modules are combined: same as the Symfony Caddyfile plus `Cache-Control: public, max-age=31536000, immutable` on `/tiles/*` (versioned map assets — PMTiles, glyphs; Caddy's file server natively supports the range requests required by the pmtiles protocol).

## v3.4.1 - 2026-06-08

Fix: add Apple Silicon (ARM64) support for the PostGIS module by switching to the multi-arch `imresamu/postgis` image

## v3.4.0 - 2026-06-08

New `postgis` module for geospatial projects

- Added the `postgis` module (`postgis/postgis:16-3.5`), a PostgreSQL 16 base with the PostGIS spatial extension enabled on both the main and test databases via `templates/shared/db/init-user-postgis.sql`.
- Auto-detection: Symfony projects requiring `longitude-one/doctrine-spatial`, `jsor/doctrine-postgis`, or any `postgis` dependency now activate `frankenphp, symfony, postgis, redis, mailpit, adminer`.
- The module reuses the PostgreSQL plumbing (port generation, container naming, `DATABASE_SERVER_VERSION`, `waitForDatabase` via `pg_isready`, first-time setup and test `.env.local` generation).

## v3.3.0 - 2026-05-29

Per-environment Doctrine server_version via DATABASE_SERVER_VERSION

- The generated `.mtdocker/.env` now defines `DATABASE_SERVER_VERSION` with the local database version (postgres 16.0, pgvector 17.0, mysql 8.0), passed to the web container by the postgres/pgvector/mysql compose modules.
- `Symfony::configureDoctrine()` now sets `server_version: '%env(default::DATABASE_SERVER_VERSION)%'` in `config/packages/doctrine.yaml` (uncommenting the default Symfony key), aligning local config with the production `DATABASE_SERVER_VERSION` env var.
- `Symfony::generateTestEnvLocal()` propagates `DATABASE_SERVER_VERSION` into `.env.test.local` for PHPStorm test runs.

## v3.2.4 - 2026-04-23

- PHP_CS_FIXER_IGNORE_ENV environment variable removed from frankenphp and apache-php compose modules.
  This variable was deprecated in PHP CS Fixer 3.x and will be removed in 4.0.
  Projects needing to run PHP CS Fixer on an unsupported PHP version should use ->setUnsupportedPhpVersionAllowed(true) in their .php-cs-fixer.dist.php config instead.

## v3.2.3 - 2026-04-09

Add quiet mode for Docker commands to reduce output noise

## v3.2.2 - 2026-04-06

Remove unnecessary dependencies and environment variables from Adminer configuration

## v3.2.1 - 2026-03-25

PHPStorm IDE Integration for Modular System

New Features

- Auto-generated .env.test.local for Symfony projects: During mtdocker init, a .env.test.local file is now generated with database connection settings (host.docker.internal, dynamic port, credentials) enabling PHPStorm to run tests directly against the Docker database container. This file is regenerated on each init to keep ports in sync.
- when@test overrides for Doctrine and Mailer: The initialization process now automatically adds when@test blocks in doctrine.yaml and mailer.yaml to use direct environment variables instead of file-based secrets (DATABASE_PASSWORD_FILE, MAILER_DSN_FILE), which are unavailable when PHPStorm runs tests outside of Docker Compose.

New Methods

- Symfony::configureDoctrineTest() — Adds password: '%env(DATABASE_PASSWORD)%' override in the when@test block of doctrine.yaml
- Symfony::generateTestEnvLocal() — Generates .env.test.local with dynamic database connection values read from .mtdocker/.env
- Symfony::configureMailerTest() — Adds dsn: '%env(MAILER_DSN)%' override in the when@test block of mailer.yaml

## v3.2.0 - 2026-03-22

Added a sandbox mode for experimenting, learning, or taking PHP exams with zero configuration. An ultra-lightweight php:cli-alpine container (~30 MB) starts in ~1 second.

Getting started:
git clone https://github.com/mulertech/docker-dev.git && cd docker-dev && ./mtdocker sandbox && code .

What's new:

- New ./mtdocker sandbox command: creates a sandbox.php file at the project root, initializes the Docker environment, and executes the script inside the container
- Generated ./run script for quick re-execution after editing
- Built-in dump() and dd() helper functions for debugging with ANSI/HTML color formatting
- New sandbox module (standalone, cannot be combined with other modules)
- No Docker build required — uses the php:cli-alpine image directly

## v3.1.4 - 2026-03-19

Remove old test command options

## v3.1.3 - 2026-03-19

Add first-time setup process for Docker environment initialization

## v3.1.2 - 2026-03-19

Add AI-optimized command handlers and update README with AI agent commands

## v3.1.1 - 2026-03-17

Replace xdebug with pcov

## v3.1.0 - 2026-03-03

Add Gotenberg module for pdf creation and rename Apache service to Web in Docker configurations and update related links and environment variables

## v3.0.0 - 2026-03-03

v3.0.0 — Modular Docker Compose Architecture

Breaking Changes

- Modular system replaces monolithic templates. The 5 fixed templates (apache-simple, apache-mysql, apache-html, symfony, symfony-pgvector-ollama) are replaced by 11 composable modules that can be freely combined.
- Existing .mtdocker/ directories must be re-initialized. Running any mtdocker command will detect the legacy compose.yml and propose automatic re-initialization.
- mtdocker init syntax changed. Instead of a template name (mtdocker init symfony), pass a comma-separated list of modules (mtdocker init frankenphp,symfony,postgres,redis,mailpit,adminer) or omit arguments for auto-detection.

New Features

- FrankenPHP as default PHP server. All auto-detected configurations now use FrankenPHP (Caddy-based) instead of Apache + mod_php. Apache remains available via the apache-php module for manual selection.
- 11 composable modules: frankenphp, apache-php, apache-html, symfony, postgres, mysql, pgvector, redis, mailpit, adminer, ollama.
- mtdocker modules command. Displays the active modules for the current project.
- New ModuleResolver class. Handles auto-detection of modules based on composer.json analysis.
- Docker Compose multi-file merge. Modules are combined via docker compose -f ... -f ..., allowing any combination of services.
- PostgreSQL 16 replaces PostgreSQL 15 for the standard postgres module.
- PostgreSQL as default database. Projects with ext-pdo now get frankenphp + postgres + adminer instead of the former apache-mysql.

Smart Auto-Detection

| Project type | Modules activated |
|--------------------|-------------------|
| Symfony + AI packages | `frankenphp`, `symfony`, `pgvector`, `ollama`, `redis`, `mailpit`, `adminer` |
| Symfony | `frankenphp`, `symfony`, `postgres`, `redis`, `mailpit`, `adminer` |
| PHP + database (ext-pdo) | `frankenphp`, `postgres`, `adminer` |
| PHP | `frankenphp` |
| Static HTML | `apache-html` |

Migration

Existing projects using the old template system will be prompted to re-initialize when running any mtdocker command. The new modules.json file in .mtdocker/ tracks the active module configuration.

## v2.0.10 - 2026-03-02

Replace shell_exec with passthru in command execution for better output handling

## v2.0.9 - 2026-02-18

Add support for file-based secrets for database password and mailer DSN in Docker configuration

## v2.0.8 - 2026-02-15

Add support for Xdebug coverage in PHPUnit Docker command

## v2.0.7 - 2026-02-15

Add support for text coverage report in testing commands (for AI)

## v2.0.6 - 2025-12-23

New command "composer" to run Composer commands inside Docker container

## v2.0.5 - 2025-12-16

Add new template based on Symfony with Pgvector and Ollama services

## v2.0.4 - 2025-11-25

Increase the memory_limit from 256M to 512M in the php.ini file

## v2.0.3 - 2025-11-16

Install wkhtmltopdf and wkhtmltoimage, replace MailHog with MailPit.

## v2.0.2 - 2025-11-12

Add support for dynamic tty flag in Docker command executions

## v2.0.1 - 2025-10-31

Fix autoload detection in mtdocker

## v2.0.0 - 2025-10-31

Added

- Object-Oriented Architecture : Complete refactoring from procedural to clean OO design
- Custom Arguments Support : All commands now accept additional arguments (e.g., `phpstan --generate-baseline`, `cs-fixer --dry-run`)
- Modular Command System : Extensible architecture for adding new commands
- CommandInterface and BaseCommand : Standardized command structure
- CommandRegistry : Centralized command management and routing
- Architecture Documentation : Added detailed architecture section in README

Changed

- Application Class : Refactored main application logic with switch-case instead of elseif chains
- Command Names : Renamed `TestCommand` to `PhpunitCommand` for future extensibility
- Error Handling : Improved GID conflict resolution in Dockerfiles
- Permission Management : Enhanced UID/GID handling for better file permissions
- Code Organization : Moved from single procedural file to organized class structure

Fixed

- GID Conflicts : Resolved Docker build failures when host GID conflicts with container system groups
- Project Directory Detection : Fixed `.mtdocker` creation in wrong location (vendor/ instead of project root)
- Dockerfile Logic : Improved user creation logic with fallback mechanisms

Technical Improvements

- Separation of Concerns : Clear separation between Composer analysis, Docker operations, and Symfony configurations
- Testability : Classes can now be unit tested independently
- Maintainability : Easier to add new commands and modify existing functionality
- Type Safety : Better use of interfaces and abstract classes

## v1.0.10 - 2025-10-09

Add link command to show the Apache server url

## v1.0.9 - 2025-09-26

Replace pgAdmin with Adminer for PostgreSQL management and update configuration

## v1.0.8 - 2025-09-26

Migrate database configuration from MySQL to PostgreSQL in Symfony Docker setup

## v1.0.7 - 2025-09-26

Add Mac OS compatibility

## v1.0.6 - 2025-09-18

Maintenance release

## v1.0.5 - 2025-09-18

Maintenance release

## v1.0.4 - 2025-09-04

Maintenance release

## v1.0.3 - 2025-08-28

Add Symfony console command support

## v1.0.2 - 2025-08-26

Maintenance release

## v1.0.1 - 2025-08-26

Maintenance release

## v1.0.0 - 2025-08-26

Initial release
