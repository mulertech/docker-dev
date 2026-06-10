<?php

namespace MulerTech\DockerDev;

/**
 * Class Docker.
 */
class Docker
{
    private Composer $composer;
    private ModuleResolver $moduleResolver;
    private bool $quiet = false;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        $this->moduleResolver = new ModuleResolver($composer);
    }

    public function setQuiet(bool $quiet): void
    {
        $this->quiet = $quiet;
    }

    public function getModuleResolver(): ModuleResolver
    {
        return $this->moduleResolver;
    }

    public function getContainerName(): string
    {
        $phpVersion = $this->composer->getPhpVersion();

        return 'docker-'.basename($this->composer->getProjectDir()).'-'.('' === $phpVersion ? 'latest' : $phpVersion);
    }

    public function getProjectName(): string
    {
        return str_replace('.', '-', $this->getContainerName());
    }

    public function getProjectBaseName(): string
    {
        return basename($this->composer->getProjectDir());
    }

    /** @param array<string> $modules */
    public function addToGitignore(array $modules = []): void
    {
        $projectDir = $this->composer->getProjectDir();
        $gitignorePath = $projectDir.'/.gitignore';

        $entries = ['.mtdocker/'];
        if (in_array('sandbox', $modules, true)) {
            $entries[] = 'sandbox.php';
            $entries[] = 'run';
        }

        if (file_exists($gitignorePath)) {
            $gitignoreContent = (string) file_get_contents($gitignorePath);
            $toAdd = [];

            foreach ($entries as $entry) {
                $check = rtrim($entry, '/');
                if (!str_contains($gitignoreContent, $check)) {
                    $toAdd[] = $entry;
                }
            }

            if ([] !== $toAdd) {
                $addition = "\n# Docker development environment\n".implode("\n", $toAdd)."\n";
                file_put_contents($gitignorePath, $addition, FILE_APPEND);
                echo 'Added '.implode(', ', $toAdd)." to .gitignore\n";
            }
        } else {
            $content = "# Docker development environment\n".implode("\n", $entries)."\n";
            file_put_contents($gitignorePath, $content);
            echo "Created .gitignore with entries\n";
        }
    }

    public function getModulesPath(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'modules';
    }

    public function getSharedPath(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'shared';
    }

    /** @param array<string> $modules */
    public function performModuleInitialization(
        array $modules,
        bool $requireConfirmation = false,
        bool $showSuccessMessage = true,
    ): bool {
        $invalid = $this->moduleResolver->validateModules($modules);
        if ([] !== $invalid) {
            echo 'Error: Unknown module(s): '.implode(', ', $invalid)."\n";
            echo 'Available modules: '.implode(', ', ModuleResolver::availableModules())."\n";

            return false;
        }

        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker';

        if (is_dir($mtdockerPath) && $requireConfirmation) {
            echo '.mtdocker directory already exists. Do you want to replace it? (y/N): ';
            $handle = fopen('php://stdin', 'r');
            if (false === $handle) {
                return false;
            }
            $response = trim((string) fgets($handle));
            fclose($handle);

            if ('y' !== strtolower($response)) {
                echo "Initialization cancelled.\n";

                return false;
            }

            exec('rm -rf '.escapeshellarg($mtdockerPath));
        }

        if (!is_dir($mtdockerPath)) {
            mkdir($mtdockerPath, 0755, true);
        }

        $dirs = $this->moduleResolver->resolveDirectoriesToCreate($modules);
        foreach ($dirs as $dir) {
            $dirPath = $mtdockerPath.DIRECTORY_SEPARATOR.$dir;
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
        }

        $sharedPath = $this->getSharedPath();
        $filesToCopy = $this->moduleResolver->resolveFilesToCopy($modules);

        foreach ($filesToCopy as $source => $target) {
            $sourcePath = $sharedPath.DIRECTORY_SEPARATOR.$source;
            $targetPath = $mtdockerPath.DIRECTORY_SEPARATOR.$target;

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (file_exists($sourcePath)) {
                copy($sourcePath, $targetPath);
            }
        }

        $this->generateEnvFile($modules, $mtdockerPath);

        $this->saveModuleConfig($modules, $mtdockerPath);

        $this->addToGitignore($modules);

        if (in_array('symfony', $modules, true)) {
            $symfony = new Symfony($this->composer);
            $symfony->configureDoctrine();
            $symfony->configureDoctrineTest();
            $symfony->configureMailer();

            $hasDatabase = [] !== array_intersect(['postgres', 'pgvector', 'postgis', 'mysql'], $modules);
            if ($hasDatabase) {
                $symfony->generateTestEnvLocal($modules, $mtdockerPath.DIRECTORY_SEPARATOR.'.env');
            }
        }

        if ($showSuccessMessage) {
            $moduleList = implode(', ', $modules);
            if ($requireConfirmation) {
                echo "Modules [$moduleList] initialized successfully in .mtdocker/\n";
                echo "You can now use: ./vendor/bin/mtdocker up -d\n";
            } else {
                echo "Auto-initialized modules: $moduleList\n";
            }
        }

        return true;
    }

    public function autoInitModules(bool $requireConfirmation = false): void
    {
        $modules = $this->moduleResolver->detectModules();
        echo 'Auto-detected modules: '.implode(', ', $modules)."\n";

        $this->performModuleInitialization($modules, $requireConfirmation);
    }

    public function ensureEnvironment(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker';
        $modulesJsonPath = $mtdockerPath.DIRECTORY_SEPARATOR.'modules.json';
        $oldComposePath = $mtdockerPath.DIRECTORY_SEPARATOR.'compose.yml';

        if (file_exists($modulesJsonPath)) {
            return;
        }

        if (file_exists($oldComposePath)) {
            echo "Legacy template detected in .mtdocker/. Re-initialization required for module system.\n";
            echo 'Do you want to re-initialize? (y/N): ';
            $handle = fopen('php://stdin', 'r');
            if (false === $handle) {
                return;
            }
            $response = trim((string) fgets($handle));
            fclose($handle);

            if ('y' === strtolower($response)) {
                exec('rm -rf '.escapeshellarg($mtdockerPath));
            } else {
                echo "Cannot continue without module configuration. Please run: mtdocker init\n";
                exit(1);
            }
        }

        echo "No .mtdocker environment found. Initializing...\n";
        $this->autoInitModules();
    }

    /** @param array<string> $modules */
    public function generateEnvFile(array $modules, string $mtdockerPath): void
    {
        if (in_array('sandbox', $modules, true)) {
            $this->generateSandboxEnvFile($mtdockerPath);

            return;
        }

        $uid = getmyuid();
        $gid = getmygid();
        $phpVersion = $this->composer->getPhpVersion();
        $phpImage = 'php:'.('' === $phpVersion ? '' : $phpVersion.'-').'apache';
        $projectBaseName = $this->getProjectBaseName();
        $webContainerName = $this->getContainerName();

        $lines = [];
        $lines[] = '# =================================';
        $lines[] = '# USER CONFIGURATION';
        $lines[] = '# =================================';
        $lines[] = "USER_ID=$uid";
        $lines[] = "GROUP_ID=$gid";
        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# DOCKER OPTIMIZATION';
        $lines[] = '# =================================';
        $lines[] = 'DOCKER_BUILDKIT=1';
        $lines[] = 'COMPOSE_DOCKER_CLI_BUILD=1';

        if (in_array('frankenphp', $modules, true)) {
            $frankenphpImage = 'dunglas/frankenphp:'.('' === $phpVersion ? 'latest' : 'php'.$phpVersion);
            $lines[] = "FRANKENPHP_IMAGE=$frankenphpImage";
        } elseif (!in_array('apache-html', $modules, true)) {
            $lines[] = "PHP_IMAGE=$phpImage";
        }

        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# PATHS (absolute, for compose modules)';
        $lines[] = '# =================================';
        $lines[] = "MTDOCKER_PATH=$mtdockerPath";
        $lines[] = 'PROJECT_PATH='.$this->composer->getProjectDir();

        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# CONTAINERS CONFIGURATION';
        $lines[] = '# =================================';
        $lines[] = "CONTAINER_NAME_WEB=$webContainerName";

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true)) {
            $lines[] = "CONTAINER_NAME_POSTGRES=$projectBaseName-postgres";
        }
        if (in_array('mysql', $modules, true)) {
            $lines[] = "CONTAINER_NAME_MYSQL=$projectBaseName-mysql";
        }
        if (in_array('adminer', $modules, true)) {
            $lines[] = "CONTAINER_NAME_ADMINER=$projectBaseName-adminer";
        }
        if (in_array('redis', $modules, true)) {
            $lines[] = "CONTAINER_NAME_REDIS=$projectBaseName-redis";
        }
        if (in_array('mailpit', $modules, true)) {
            $lines[] = "CONTAINER_NAME_MAILPIT=$projectBaseName-mailpit";
        }
        if (in_array('ollama', $modules, true)) {
            $lines[] = "CONTAINER_NAME_OLLAMA=$projectBaseName-ollama";
        }
        if (in_array('gotenberg', $modules, true)) {
            $lines[] = "CONTAINER_NAME_GOTENBERG=$projectBaseName-gotenberg";
        }

        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# PORTS CONFIGURATION';
        $lines[] = '# =================================';

        $projectName = $projectBaseName;
        $webPort = $this->generatePortFromName($projectName);
        $lines[] = "# Web server (http://localhost:$webPort)";
        $lines[] = "WEB_PORT=$webPort";

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-postgres');
            $lines[] = "# PostgreSQL (localhost:$port)";
            $lines[] = "POSTGRES_PORT=$port";
        }
        if (in_array('mysql', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-mysql');
            $lines[] = "# MySQL (localhost:$port)";
            $lines[] = "MYSQL_PORT=$port";
        }
        if (in_array('adminer', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-adminer');
            $lines[] = "# Adminer (http://localhost:$port)";
            $lines[] = "ADMINER_PORT=$port";
        }
        if (in_array('redis', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-redis');
            $lines[] = "REDIS_PORT=$port";
        }
        if (in_array('mailpit', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-mailpit');
            $smtpPort = $this->generatePortFromName($projectName.'-mailpit-smtp');
            $lines[] = "# MailPit web (http://localhost:$port)";
            $lines[] = "MAILPIT_PORT=$port";
            $lines[] = "MAILPIT_SMTP_PORT=$smtpPort";
        }
        if (in_array('ollama', $modules, true)) {
            $port = $this->generatePortFromName($projectName.'-ollama');
            $lines[] = "OLLAMA_PORT=$port";
        }

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true)) {
            $serverVersion = in_array('pgvector', $modules, true) ? '17.0' : '16.0';
            $lines[] = '';
            $lines[] = '# =================================';
            $lines[] = '# DATABASE CONFIGURATION';
            $lines[] = '# =================================';
            $lines[] = 'DB_NAME=db';
            $lines[] = 'DB_USER=user';
            $lines[] = 'DB_PASSWORD=password';
            $lines[] = '# Doctrine server_version (consumed in config/packages/doctrine.yaml)';
            $lines[] = "DATABASE_SERVER_VERSION=$serverVersion";
        }

        if (in_array('mysql', $modules, true)) {
            $lines[] = '';
            $lines[] = '# =================================';
            $lines[] = '# DATABASE CONFIGURATION';
            $lines[] = '# =================================';
            $lines[] = 'DB_NAME=db';
            $lines[] = 'DB_USER=user';
            $lines[] = 'DB_PASSWORD=password';
            $lines[] = 'DB_ROOT_PASSWORD=root';
            $lines[] = '# Doctrine server_version (consumed in config/packages/doctrine.yaml)';
            $lines[] = 'DATABASE_SERVER_VERSION=8.0';
        }

        if ($this->composer->needsWkhtmltopdf()) {
            $lines[] = '';
            $lines[] = '# =================================';
            $lines[] = '# PDF GENERATION (wkhtmltopdf)';
            $lines[] = '# =================================';
            $lines[] = 'INSTALL_WKHTMLTOPDF=1';
        }

        if (in_array('gotenberg', $modules, true)) {
            $lines[] = '';
            $lines[] = '# =================================';
            $lines[] = '# PDF GENERATION (Gotenberg)';
            $lines[] = '# =================================';
            $lines[] = 'GOTENBERG_URL=http://gotenberg:3000';
        }

        $envContent = implode("\n", $lines)."\n";
        file_put_contents($mtdockerPath.DIRECTORY_SEPARATOR.'.env', $envContent);
    }

    /** @param array<string> $modules */
    public function saveModuleConfig(array $modules, string $mtdockerPath): void
    {
        $configPath = $mtdockerPath.DIRECTORY_SEPARATOR.'modules.json';
        file_put_contents($configPath, json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /** @return array<string> */
    public function loadModuleConfig(): array
    {
        $projectDir = $this->composer->getProjectDir();
        $configPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker'.DIRECTORY_SEPARATOR.'modules.json';

        if (!file_exists($configPath)) {
            return [];
        }

        $content = (string) file_get_contents($configPath);
        $modules = json_decode($content, true);

        if (!is_array($modules)) {
            return [];
        }

        $strings = [];
        foreach ($modules as $module) {
            if (is_string($module)) {
                $strings[] = $module;
            }
        }

        return $strings;
    }

    public function dockerComposeCommand(): string
    {
        $projectDir = $this->composer->getProjectDir();
        $mtdockerPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker';
        $modulesPath = $this->getModulesPath();
        $modules = $this->loadModuleConfig();

        $envVars = 'DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 ';

        $composeFiles = '';
        $baseFirst = ['frankenphp', 'apache-php', 'apache-html', 'sandbox'];
        $orderedModules = [];

        foreach ($baseFirst as $base) {
            if (in_array($base, $modules, true)) {
                $orderedModules[] = $base;
            }
        }
        foreach ($modules as $module) {
            if (!in_array($module, $baseFirst, true)) {
                $orderedModules[] = $module;
            }
        }

        foreach ($orderedModules as $module) {
            $composeFile = $modulesPath.DIRECTORY_SEPARATOR.'compose.'.$module.'.yml';
            $composeFiles .= ' -f '.$composeFile;
        }

        return $envVars.'docker compose --env-file '.$mtdockerPath.DIRECTORY_SEPARATOR.'.env'.$composeFiles.' --project-name '.$this->getProjectName();
    }

    public function getWebPort(): int
    {
        $projectDir = $this->composer->getProjectDir();
        $envPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker'.DIRECTORY_SEPARATOR.'.env';

        if (file_exists($envPath)) {
            $envContent = (string) file_get_contents($envPath);
            if (preg_match('/WEB_PORT=(\d+)/', $envContent, $matches)) {
                return (int) $matches[1];
            }
        }

        return 8080;
    }

    public function displayWebLink(): void
    {
        $port = $this->getWebPort();
        echo "\n🚀 Web server is running at: \033]8;;http://localhost:$port\033\\http://localhost:$port\033]8;;\033\\\n\n";
    }

    public function dockerComposeUp(string $arg2): void
    {
        $this->ensureEnvironment();

        $command = $this->dockerComposeCommand().' up';
        $command .= '-d' === $arg2 ? ' -d' : '';

        if ('-d' === $arg2) {
            if ($this->quiet) {
                exec($command.' 2>&1', $output, $exitCode);

                if (0 !== $exitCode) {
                    echo "Error starting containers (exit code: $exitCode)\n";
                }

                $this->runFirstTimeSetup();
            } else {
                echo "🚀 Starting Docker containers...\n";

                $exitCode = 0;
                passthru($command.' 2>&1', $exitCode);

                if (0 === $exitCode) {
                    echo "\n✅ All containers started successfully!\n";
                    $this->runFirstTimeSetup();
                    $this->displayWebLink();
                } else {
                    echo "\n❌ Error starting containers (exit code: $exitCode)\n";
                }
            }
        } else {
            passthru($command);
        }
    }

    public function dockerComposeDown(): void
    {
        $this->ensureEnvironment();
        $command = $this->dockerComposeCommand().' down';
        exec($command);
    }

    public function isDockerUp(): bool
    {
        return str_contains((string) exec('docker compose ls | grep '.$this->getProjectName()), $this->getProjectName());
    }

    public function runFirstTimeSetup(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $markerPath = $projectDir.DIRECTORY_SEPARATOR.'.mtdocker'.DIRECTORY_SEPARATOR.'.setup-done';

        if (file_exists($markerPath)) {
            return;
        }

        $modules = $this->loadModuleConfig();
        if ([] === $modules) {
            return;
        }

        $containerName = $this->getContainerName();
        $isSymfony = in_array('symfony', $modules, true);
        $hasDb = in_array('postgres', $modules, true)
            || in_array('pgvector', $modules, true)
            || in_array('postgis', $modules, true)
            || in_array('mysql', $modules, true);

        if (!is_dir($projectDir.DIRECTORY_SEPARATOR.'vendor')) {
            if (!$this->quiet) {
                echo "Installing Composer dependencies...\n";
            }
            $this->quietPassthru('docker exec '.escapeshellarg($containerName).' composer install --no-interaction');
        }

        if ($isSymfony) {
            if ($this->composer->hasFile('package.json') && !is_dir($projectDir.DIRECTORY_SEPARATOR.'node_modules')) {
                if (!$this->quiet) {
                    echo "Installing npm dependencies...\n";
                }
                $this->quietPassthru('docker exec '.escapeshellarg($containerName).' npm install');
            }

            if ($this->composer->hasPackage('symfonycasts/tailwind-bundle')) {
                if (!$this->quiet) {
                    echo "Building Tailwind CSS...\n";
                }
                $this->quietPassthru('docker exec '.escapeshellarg($containerName).' php bin/console tailwind:build');
            }

            if ($hasDb && $this->composer->hasPackage('doctrine/doctrine-migrations-bundle')) {
                $this->waitForDatabase($containerName, $modules);
                if (!$this->quiet) {
                    echo "Running test database migrations...\n";
                }
                $this->quietPassthru('docker exec '.escapeshellarg($containerName).' php bin/console doctrine:migrations:migrate --no-interaction --env=test');
            }
        }

        file_put_contents($markerPath, date('Y-m-d H:i:s')."\n");
        if (!$this->quiet) {
            echo "First-time setup completed.\n";
        }
    }

    private function quietPassthru(string $command): void
    {
        if ($this->quiet) {
            exec($command.' 2>&1');
        } else {
            passthru($command);
        }
    }

    /** @param array<string> $modules */
    private function waitForDatabase(string $containerName, array $modules): void
    {
        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true)) {
            $dbContainer = $this->getProjectBaseName().'-postgres';
            for ($i = 0; $i < 30; ++$i) {
                $result = exec('docker exec '.escapeshellarg($dbContainer).' pg_isready -U user 2>/dev/null', $output, $exitCode);
                if (0 === $exitCode) {
                    return;
                }
                sleep(1);
            }
            echo "Warning: Database may not be ready yet.\n";
        } elseif (in_array('mysql', $modules, true)) {
            $dbContainer = $this->getProjectBaseName().'-mysql';
            for ($i = 0; $i < 30; ++$i) {
                exec('docker exec '.escapeshellarg($dbContainer).' mysqladmin ping -u user -ppassword 2>/dev/null', $output, $exitCode);
                if (0 === $exitCode) {
                    return;
                }
                sleep(1);
            }
            echo "Warning: Database may not be ready yet.\n";
        }
    }

    private function generatePortFromName(string $name): int
    {
        $hash = md5($name);
        $port = 1024 + (hexdec(substr($hash, 0, 4)) % 48128);

        return $this->findAvailablePortFromBase($port);
    }

    private function findAvailablePortFromBase(int $basePort): int
    {
        for ($port = $basePort; $port < $basePort + 100; ++$port) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
            if (!$connection) {
                return $port;
            }
            fclose($connection);
        }

        return $basePort;
    }

    public function getSandboxContainerName(): string
    {
        return $this->getProjectBaseName().'-sandbox';
    }

    private function generateSandboxEnvFile(string $mtdockerPath): void
    {
        $phpVersion = $this->composer->getPhpVersion();

        $lines = [];
        $lines[] = '# =================================';
        $lines[] = '# PHP VERSION';
        $lines[] = '# =================================';
        $lines[] = 'PHP_VERSION_TAG='.('' === $phpVersion ? '8.4' : $phpVersion);
        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# PATHS';
        $lines[] = '# =================================';
        $lines[] = 'PROJECT_PATH='.$this->composer->getProjectDir();
        $lines[] = '';
        $lines[] = '# =================================';
        $lines[] = '# CONTAINER CONFIGURATION';
        $lines[] = '# =================================';
        $lines[] = 'CONTAINER_NAME_SANDBOX='.$this->getSandboxContainerName();

        $envContent = implode("\n", $lines)."\n";
        file_put_contents($mtdockerPath.DIRECTORY_SEPARATOR.'.env', $envContent);
    }

    public function initSandboxFiles(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $sharedPath = $this->getSharedPath().DIRECTORY_SEPARATOR.'sandbox';

        $sandboxPath = $projectDir.DIRECTORY_SEPARATOR.'sandbox.php';
        if (!file_exists($sandboxPath)) {
            copy($sharedPath.DIRECTORY_SEPARATOR.'sandbox.php', $sandboxPath);
        }

        $runPath = $projectDir.DIRECTORY_SEPARATOR.'run';
        if (!file_exists($runPath)) {
            file_put_contents($runPath, "#!/bin/bash\n./mtdocker sandbox\n");
            chmod($runPath, 0755);
        }
    }

    public function ensureSandboxEnvironment(): void
    {
        $modules = $this->loadModuleConfig();

        if (in_array('sandbox', $modules, true)) {
            return;
        }

        $this->performModuleInitialization(['sandbox'], false, false);
        $this->initSandboxFiles();
    }

    public function isSandboxUp(): bool
    {
        $containerName = $this->getSandboxContainerName();
        exec('docker ps --filter name='.escapeshellarg($containerName).' --filter status=running --format "{{.Names}}"', $output);

        return in_array($containerName, $output, true);
    }

    public function sandboxUp(): void
    {
        $command = $this->dockerComposeCommand().' up -d';
        exec($command.' 2>&1');
    }

    public function sandboxDown(): void
    {
        $command = $this->dockerComposeCommand().' down';
        exec($command.' 2>&1');
    }
}
