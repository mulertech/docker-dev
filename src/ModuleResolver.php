<?php

namespace MulerTech\DockerDev;

/**
 * Class ModuleResolver.
 */
class ModuleResolver
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /** @return array<string> */
    public function detectModules(): array
    {
        $modules = [];

        if ($this->composer->isSymfonyProject()) {
            $modules[] = 'frankenphp';
            $modules[] = 'symfony';

            if ($this->composer->needsPgvectorAndOllama()) {
                $modules[] = 'pgvector';
                $modules[] = 'ollama';
            } elseif ($this->composer->needsPostgis()) {
                $modules[] = 'postgis';
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
        } elseif ('' !== $this->composer->getPhpVersion()) {
            $modules[] = 'frankenphp';
        } else {
            $modules[] = 'apache-html';
        }

        return $modules;
    }

    /** @param array<string> $modules */
    public function resolveDockerfile(array $modules): string
    {
        if (in_array('sandbox', $modules, true)) {
            return '';
        }

        if (in_array('apache-html', $modules, true)) {
            return 'html'.DIRECTORY_SEPARATOR.'Dockerfile';
        }

        $isFrankenPhp = in_array('frankenphp', $modules, true);

        if (in_array('symfony', $modules, true)) {
            return 'php'.DIRECTORY_SEPARATOR.($isFrankenPhp ? 'Dockerfile.frankenphp-symfony' : 'Dockerfile.symfony');
        }

        if ($isFrankenPhp) {
            return 'php'.DIRECTORY_SEPARATOR.'Dockerfile.frankenphp';
        }

        if (in_array('mysql', $modules, true)) {
            return 'php'.DIRECTORY_SEPARATOR.'Dockerfile.mysql';
        }

        return 'php'.DIRECTORY_SEPARATOR.'Dockerfile.simple';
    }

    /**
     * @param array<string> $modules
     *
     * @return array<string, string>
     */
    public function resolveFilesToCopy(array $modules): array
    {
        if (in_array('sandbox', $modules, true)) {
            return [];
        }

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
                // Geospatial projects get extra Caddy rules (immutable cache on /tiles/*).
                $caddyfile = in_array('postgis', $modules, true) ? 'php/Caddyfile.symfony-postgis' : 'php/Caddyfile.symfony';
                $files[$caddyfile] = 'php/Caddyfile';
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

        if (in_array('postgis', $modules, true)) {
            $files['db/init-user-postgis.sql'] = 'db/init-user.sql';
            $files['secrets/db_password'] = 'secrets/db_password';
        }

        if (in_array('mysql', $modules, true)) {
            $files['db/init-user-mysql.sql'] = 'db/init-user.sql';
        }

        if (in_array('symfony', $modules, true)) {
            $files['secrets/mailer_dsn'] = 'secrets/mailer_dsn';
            $files['load.md'] = 'load.md';
        }

        if (in_array('adminer', $modules, true)) {
            $files['adminer/adminer-index.php'] = 'adminer/adminer-index.php';
        }

        return $files;
    }

    /**
     * @param array<string> $modules
     *
     * @return array<string>
     */
    public function resolveDirectoriesToCreate(array $modules): array
    {
        if (in_array('sandbox', $modules, true)) {
            return [];
        }

        $dirs = [];

        if (in_array('apache-html', $modules, true)) {
            $dirs[] = 'html';
        } else {
            $dirs[] = 'php';
        }

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true) || in_array('mysql', $modules, true)) {
            $dirs[] = 'db';
            $dirs[] = 'sql';
        }

        if (in_array('postgres', $modules, true) || in_array('pgvector', $modules, true) || in_array('postgis', $modules, true) || in_array('symfony', $modules, true)) {
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

    /** @return array<string> */
    public static function availableModules(): array
    {
        return [
            'frankenphp',
            'apache-php',
            'apache-html',
            'symfony',
            'postgres',
            'postgis',
            'mysql',
            'pgvector',
            'redis',
            'mailpit',
            'adminer',
            'ollama',
            'gotenberg',
            'sandbox',
        ];
    }

    /**
     * @param array<string> $modules
     *
     * @return array<string>
     */
    public function validateModules(array $modules): array
    {
        $available = self::availableModules();
        $invalid = array_diff($modules, $available);

        if ([] !== $invalid) {
            return $invalid;
        }

        if (in_array('sandbox', $modules, true) && count($modules) > 1) {
            echo "Error: The sandbox module cannot be combined with other modules.\n";

            return ['sandbox (exclusive)'];
        }

        return [];
    }
}
