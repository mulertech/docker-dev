# Agent Guidelines for docker-dev

## Build/Test/Lint Commands
- Run all tests: `./vendor/bin/mtdocker test`
- Run tests with coverage: `./vendor/bin/mtdocker test-coverage`
- Run PHPStan analysis: `./vendor/bin/mtdocker phpstan`
- Run PHP CS Fixer: `./vendor/bin/mtdocker cs-fixer`
- Run all checks: `./vendor/bin/mtdocker all` (runs cs-fixer, test, phpstan)
- Single test: `./vendor/bin/mtdocker test --filter=TestClassName::testMethodName`

## Code Style & Conventions
- **Language**: PHP 8.0+ (check composer.json for project-specific version)
- **No comments**: Code should be self-documenting unless explicitly needed
- **Functions**: Use descriptive names in camelCase (e.g., `getProjectDir()`, `isDockerUp()`)
- **String operations**: Use native PHP functions (str_contains, str_replace) over regex when possible
- **Error handling**: Use simple conditionals and early returns; no try-catch unless necessary
- **File paths**: Use DIRECTORY_SEPARATOR constant for cross-platform compatibility
- **Security**: Never expose credentials; use environment variables in .env files
- **Docker**: All templates use Docker Compose; environment auto-initialization on first command
- **Shell commands**: Use escapeshellarg() for user input, passthru() for interactive commands
