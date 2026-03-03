<?php

namespace MulerTech\DockerDev;

/**
 * Class ModuleResolver
 * @package MulerTech\DockerDev
 */
class ModuleResolver
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function detectModules(): array
    {
        $modules = [];

        if ($this->composer->isSymfonyProject()) {
            $modules[] = 'frankenphp';
            $modules[] = 'symfony';

            if ($this->composer->needsPgvectorAndOllama()) {
                $modules[] = 'pgvector';
                $modules[] = 'ollama';
            } else {
                $modules[] = 'postgres';
            }

            $modules[] = 'redis';
            $modules[] = 'mailpit';
            $modules[] = 'adminer';

            if ($this->composer->needsGotenberg()) {
                $modules[] = 'gotenberg';
            }
        } elseif ($this->composer->dbNeeded()) {
            $modules[] = 'frankenphp';
            $modules[] = 'postgres';
            $modules[] = 'adminer';
        } elseif ($this->composer->getPhpVersion() !== '') {
            $modules[] = 'frankenphp';
        } else {
            $modules[] = 'apache-html';
        }

        return $modules;
    }

    public function resolveDockerfile(array $modules): string
    {
        if (in_array('apache-html', $modules, true)) {
            return 'html' . DIRECTORY_SEPARATOR . 'Dockerfile';
        }

        $isFrankenPhp = in_array('frankenphp', $modules, true);

        if (in_array('symfony', $modules, true)) {
            return 'php' . DIRECTORY_SEPARATOR . ($isFrankenPhp ? 'Dockerfile.frankenphp-symfony' : 'Dockerfile.symfony');
        }

        if ($isFrankenPhp) {
            return 'php' . DIRECTORY_SEPARATOR . 'Dockerfile.frankenphp';
        }

        if (in_array('mysql', $modules, true)) {
            return 'php' . DIRECTORY_SEPARATOR . 'Dockerfile.mysql';
        }

        return 'php' . DIRECTORY_SEPARATOR . 'Dockerfile.simple';
    }

    public function resolveFilesToCopy(array $modules): array
    {
        $files = [];

        $dockerfile = $this->resolveDockerfile($modules);
        if (str_starts_with($dockerfile, 'html')) {
            $files['html/Dockerfile'] = 'html/Dockerfile';
        } else {
            $targetName = str_contains($dockerfile, 'Dockerfile.') ? 'php/Dockerfile' : $dockerfile;
            $files[$dockerfile] = $targetName;
        }

        $isFrankenPhp = in_array('frankenphp', $modules, true);

        if ($isFrankenPhp) {
            if (in_array('symfony', $modules, true)) {
                $files['php/Caddyfile.symfony'] = 'php/Caddyfile';
            } else {
                $files['php/Caddyfile'] = 'php/Caddyfile';
            }
        }

        if (in_array('symfony', $modules, true)) {
            $files['php/apache.conf'] = 'php/apache.conf';
            $files['php/php.ini'] = 'php/php.ini';
            $files['.dockerignore'] = '.dockerignore';
        }

        if (in_array('postgres', $modules, true)) {
            $files['db/init-user-postgres.sql'] = 'db/init-user.sql';
            $files['secrets/db_password'] = 'secrets/db_password';
        }

        if (in_array('pgvector', $modules, true)) {
            $files['db/init-user-pgvector.sql'] = 'db/init-user.sql';
            $files['secrets/db_password'] = 'secrets/db_password';
        }

        if (in_array('mysql', $modules, true)) {
            $files['db/init-user-mysql.sql'] = 'db/init-user.sql';
        }

        if (in_array('symfony', $modules, true)) {
            $files['secrets/mailer_dsn'] = 'secrets/mailer_dsn';
        }

        if (in_array('adminer', $modules, true)) {
            $files['adminer/adminer-index.php'] = 'adminer/adminer-index.php';
        }

        return $files;
    }

    public function resolveDirectoriesToCreate(array $modules): array
    {
        $dirs = [];

        if (in_array('apache-html', $modules, true)) {
            $dirs[] = 'html';
        } else {
            $dirs[] = 'php';
        }

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('mysql', $modules, true)) {
            $dirs[] = 'db';
            $dirs[] = 'sql';
        }

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('symfony', $modules, true)) {
            $dirs[] = 'secrets';
        }

        if (in_array('adminer', $modules, true)) {
            $dirs[] = 'adminer';
        }

        if (in_array('ollama', $modules, true)) {
            $dirs[] = 'ollama';
        }

        return array_unique($dirs);
    }

    public static function availableModules(): array
    {
        return [
            'frankenphp',
            'apache-php',
            'apache-html',
            'symfony',
            'postgres',
            'mysql',
            'pgvector',
            'redis',
            'mailpit',
            'adminer',
            'ollama',
            'gotenberg',
        ];
    }

    public function validateModules(array $modules): array
    {
        $available = self::availableModules();
        $invalid = array_diff($modules, $available);

        return $invalid;
    }
}
