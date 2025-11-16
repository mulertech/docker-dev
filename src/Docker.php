<?php

namespace MulerTech\DockerDev;

/**
 * Class Docker
 * @package MulerTech\DockerDev
 */
class Docker
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function detectTemplate(): string
    {
        // Priority 1: Symfony framework
        if ($this->composer->isSymfonyProject()) {
            return 'symfony';
        }

        // Priority 2: Database needed
        if ($this->composer->dbNeeded()) {
            return 'apache-mysql';
        }

        // Priority 3: Simple Apache + PHP
        return 'apache-simple';
    }

    public function getContainerName(): string
    {
        $phpVersion = $this->composer->getPhpVersion();
        return 'docker-' . basename($this->composer->getProjectDir()) . '-' . ($phpVersion === '' ? 'latest' : $phpVersion);
    }

    public function getProjectName(): string
    {
        return str_replace('.', '-', $this->getContainerName());
    }

    public function getProjectBaseName(): string
    {
        return basename($this->composer->getProjectDir());
    }

    public function addToGitignore(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $gitignorePath = $projectDir . '/.gitignore';

        if (file_exists($gitignorePath)) {
            $gitignoreContent = file_get_contents($gitignorePath);

            // Check if .mtdocker is already ignored
            if (!str_contains($gitignoreContent, '.mtdocker')) {
                $addition = "\n# Docker development environment\n.mtdocker/\n";
                file_put_contents($gitignorePath, $addition, FILE_APPEND);
                echo "Added .mtdocker/ to .gitignore\n";
            }
        } else {
            // Create .gitignore with .mtdocker entry
            $content = "# Docker development environment\n.mtdocker/\n";
            file_put_contents($gitignorePath, $content);
            echo "Created .gitignore with .mtdocker/ entry\n";
        }
    }

    public function performTemplateInitialization(
        string $template,
        bool $requireConfirmation = false,
        bool $showSuccessMessage = true
    ): bool {
        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir . DIRECTORY_SEPARATOR . '.mtdocker';
        $templatesPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;

        if (!is_dir($templatesPath)) {
            echo "Error: Template '$template' not found.\n";
            if ($requireConfirmation) {
                echo "Templates available: apache-simple, apache-mysql, symfony\n";
            }
            return false;
        }

        // Handle existing directory with confirmation if required
        if (is_dir($mtdockerPath) && $requireConfirmation) {
            echo ".mtdocker directory already exists. Do you want to replace it? (y/N): ";
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));
            fclose($handle);

            if (strtolower($response) !== 'y') {
                echo "Initialization cancelled.\n";
                return false;
            }

            exec("rm -rf " . escapeshellarg($mtdockerPath));
        } elseif (!is_dir($mtdockerPath)) {
            // Create .mtdocker directory if it doesn't exist
            mkdir($mtdockerPath, 0755, true);
        }

        // Copy template files (including hidden files)
        exec("cp -r " . escapeshellarg($templatesPath) . "/. " . escapeshellarg($mtdockerPath) . " 2>/dev/null || true");
        exec("cp -r " . escapeshellarg($templatesPath) . "/* " . escapeshellarg($mtdockerPath) . " 2>/dev/null || true");

        // Process .env file from .env.example
        $envExamplePath = $mtdockerPath . DIRECTORY_SEPARATOR . '.env.example';
        $envPath = $mtdockerPath . DIRECTORY_SEPARATOR . '.env';

        if (file_exists($envExamplePath)) {
            $envContent = file_get_contents($envExamplePath);

            // Auto-detect USER_ID and GROUP_ID
            $uid = getmyuid();
            $gid = getmygid();
            $envContent = str_replace(
                    ['USER_ID=1000', 'GROUP_ID=1000'],
                    ["USER_ID=$uid", "GROUP_ID=$gid"],
                    $envContent
            );

            // Auto-detect PHP version and image
            $phpVersion = $this->composer->getPhpVersion();
            $phpImage = 'php:' . ($phpVersion === '' ? '' : $phpVersion . '-') . 'apache';
            $envContent = str_replace('PHP_IMAGE=php:apache', "PHP_IMAGE=$phpImage", $envContent);

            // Generate container names based on project (for Symfony template)
            if ($template === 'symfony') {
                $apacheContainerName = $this->getContainerName();
                $projectBaseName = $this->getProjectBaseName();
                $envContent = str_replace(
                    [
                        'CONTAINER_NAME_APACHE=apache-php',
                        'CONTAINER_NAME_POSTGRES=postgres-db',
                        'CONTAINER_NAME_ADMINER=symfony-adminer',
                        'CONTAINER_NAME_REDIS=symfony-redis',
                        'CONTAINER_NAME_MAILPIT=symfony-mailpit'
                    ],
                    [
                        "CONTAINER_NAME_APACHE=$apacheContainerName",
                        "CONTAINER_NAME_POSTGRES=$projectBaseName-postgres",
                        "CONTAINER_NAME_ADMINER=$projectBaseName-adminer",
                        "CONTAINER_NAME_REDIS=$projectBaseName-redis",
                        "CONTAINER_NAME_MAILPIT=$projectBaseName-mailpit"
                    ],
                    $envContent
                );
            }

            // Generate available ports
            $envContent = $this->generateAvailablePorts($envContent);

            file_put_contents($envPath, $envContent);

            if ($requireConfirmation) {
                echo ".env file created with your system settings.\n";
            }

            // Remove .env.example after processing
            unlink($envExamplePath);
        }

        // Add .mtdocker to .gitignore
        $this->addToGitignore();

        // Configure Symfony Doctrine if it's a Symfony template
        if ($template === 'symfony') {
            $symfony = new Symfony($this->composer);
            $symfony->configureDoctrine();
            $symfony->configureMailer();
        }

        if ($showSuccessMessage) {
            if ($requireConfirmation) {
                echo "Template '$template' initialized successfully in .mtdocker/\n";
                echo "You can now use: ./vendor/bin/mtdocker up -d\n";
            } else {
                echo "Auto-initialized '$template' template.\n";
            }
        }

        return true;
    }

    public function ensureEnvironment(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir . DIRECTORY_SEPARATOR . '.mtdocker';

        if (!file_exists($mtdockerPath . DIRECTORY_SEPARATOR . 'compose.yml')) {
            echo "No .mtdocker environment found. Initializing...\n";
            $this->autoInitTemplate();
        }
    }

    public function autoInitTemplate(): void
    {
        // Auto-detect template based on project
        $template = $this->detectTemplate();

        // Use the common initialization function without confirmation
        $this->performTemplateInitialization($template);
    }

    public function dockerComposeCommand(): string
    {
        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir . DIRECTORY_SEPARATOR . '.mtdocker';

        // Enable BuildKit for faster builds
        $envVars = 'DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 ';

        return $envVars . 'docker compose -f ' . $mtdockerPath . DIRECTORY_SEPARATOR . 'compose.yml --project-directory ' . $mtdockerPath . ' --project-name ' . $this->getProjectName();
    }

    public function getApachePort(): int
    {
        $projectDir = $this->composer->getProjectDir();
        $envPath = $projectDir . DIRECTORY_SEPARATOR . '.mtdocker' . DIRECTORY_SEPARATOR . '.env';

        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (preg_match('/APACHE_PORT=(\d+)/', $envContent, $matches)) {
                return (int)$matches[1];
            }
        }

        return 8080; // fallback
    }

    public function displayApacheLink(): void
    {
        $port = $this->getApachePort();
        echo "\n🚀 Apache server is running at: \033]8;;http://localhost:$port\033\\http://localhost:$port\033]8;;\033\\\n\n";
    }

    public function dockerComposeUp(string $arg2): void
    {
        $this->ensureEnvironment();

        $command = $this->dockerComposeCommand() . ' up';
        $command .= $arg2 === '-d' ? ' -d' : '';

        if ($arg2 === '-d') {
            echo "🚀 Starting Docker containers...\n";

            $exitCode = 0;
            passthru($command . ' 2>&1', $exitCode);

            if ($exitCode === 0) {
                echo "\n✅ All containers started successfully!\n";
                $this->displayApacheLink();
            } else {
                echo "\n❌ Error starting containers (exit code: $exitCode)\n";
            }
        } else {
            // For non-detached mode, use passthru to display output
            passthru($command);
        }
    }

    public function dockerComposeDown(): void
    {
        $this->ensureEnvironment();
        $command = $this->dockerComposeCommand() . ' down';
        exec($command);
    }

    public function isDockerUp(): bool
    {
        return str_contains(exec('docker compose ls | grep ' . $this->getProjectName()), $this->getProjectName());
    }

    private function generateAvailablePorts(string $envContent): string
    {
        $projectName = basename($this->composer->getProjectDir());

        // Base ports that are always present
        $ports = [
                'APACHE_PORT' => $this->generatePortFromName($projectName)
        ];

        // Add other ports only if they exist in the .env.example
        if (str_contains($envContent, 'MYSQL_PORT=')) {
            $ports['MYSQL_PORT'] = $this->generatePortFromName($projectName . '-mysql');
        }
        if (str_contains($envContent, 'POSTGRES_PORT=')) {
            $ports['POSTGRES_PORT'] = $this->generatePortFromName($projectName . '-postgres');
        }
        if (str_contains($envContent, 'PHPMYADMIN_PORT=')) {
            $ports['PHPMYADMIN_PORT'] = $this->generatePortFromName($projectName . '-phpmyadmin');
        }
        if (str_contains($envContent, 'ADMINER_PORT=')) {
            $ports['ADMINER_PORT'] = $this->generatePortFromName($projectName . '-adminer');
        }
        if (str_contains($envContent, 'REDIS_PORT=')) {
            $ports['REDIS_PORT'] = $this->generatePortFromName($projectName . '-redis');
        }
        if (str_contains($envContent, 'MAILPIT_PORT=')) {
            $ports['MAILPIT_PORT'] = $this->generatePortFromName($projectName . '-mailpit');
        }
        if (str_contains($envContent, 'MAILPIT_SMTP_PORT=')) {
            $ports['MAILPIT_SMTP_PORT'] = $this->generatePortFromName($projectName . '-mailpit-smtp');
        }

        foreach ($ports as $varName => $port) {
            // Replace port values
            $envContent = preg_replace("/^$varName=\d+$/m", "$varName=$port", $envContent) ?? $envContent;
            $envContent = (string)$envContent;

            // Update comments with actual generated ports
            if ($varName === 'APACHE_PORT') {
                $envContent = str_replace(
                    '# Port to access Apache web server (ex: http://localhost:8080)',
                    "# Port to access Apache web server (http://localhost:$port)",
                    $envContent
                );
            } elseif ($varName === 'MYSQL_PORT') {
                $envContent = str_replace(
                    '# Port to access MySQL directly (ex: localhost:3307)',
                    "# Port to access MySQL directly (localhost:$port)",
                    $envContent
                );
            } elseif ($varName === 'POSTGRES_PORT') {
                $envContent = str_replace(
                    '# Port to access PostgreSQL directly (ex: localhost:5433)',
                    "# Port to access PostgreSQL directly (localhost:$port)",
                    $envContent
                );
            } elseif ($varName === 'PHPMYADMIN_PORT') {
                $envContent = str_replace(
                    '# Port to access PhpMyAdmin (ex: http://localhost:8081)',
                    "# Port to access PhpMyAdmin (http://localhost:$port)",
                    $envContent
                );
            } elseif ($varName === 'ADMINER_PORT') {
                $envContent = str_replace(
                    '# Port to access Adminer (ex: http://localhost:8081)',
                    "# Port to access Adminer (http://localhost:$port)",
                    $envContent
                );
            } elseif ($varName === 'MAILPIT_PORT') {
                $envContent = str_replace(
                    '# Port for MailPit web interface (ex: http://localhost:8025)',
                    "# Port for MailPit web interface (http://localhost:$port)",
                    $envContent
                );
            }
        }

        return $envContent;
    }

    private function generatePortFromName(string $name): int
    {
        // Generate a deterministic port based on the name hash
        $hash = md5($name);
        // Convert first 4 hex chars to decimal and map to port range 1024-49151
        $port = 1024 + (hexdec(substr($hash, 0, 4)) % 48128);

        // Ensure the port is available, if not increment until we find one
        return $this->findAvailablePortFromBase($port);
    }

    private function findAvailablePortFromBase(int $basePort): int
    {
        // Start from the base port and find the first available one
        for ($port = $basePort; $port < $basePort + 100; $port++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
            if (!$connection) {
                return $port;
            }
            fclose($connection);
        }

        // If no port is available in the range, fall back to the base port
        return $basePort;
    }
}