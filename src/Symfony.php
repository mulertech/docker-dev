<?php

namespace MulerTech\DockerDev;

/**
 * Class Symfony
 * @package MulerTech\DockerDev
 */
class Symfony
{
    private Composer $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function configureDoctrine(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $doctrinePath = $projectDir . '/config/packages/doctrine.yaml';

        if (!file_exists($doctrinePath)) {
            return; // Skip if doctrine.yaml doesn't exist
        }

        $doctrineContent = file_get_contents($doctrinePath);

        // Check if already modified (avoid duplicate modifications)
        if (str_contains($doctrineContent, "# Modified by mulertech/docker-dev package for Docker environment")) {
            return; // Already configured
        }

        // Replace the DATABASE_URL configuration with flexible fallback configuration
        $oldConfig = "url: '%env(resolve:DATABASE_URL)%'";
        $newConfig = "# Modified by mulertech/docker-dev package for Docker environment
        host: '%env(default::DATABASE_HOST)%'
        port: '%env(default::DATABASE_PORT)%'
        dbname: '%env(default::DATABASE_NAME)%'
        user: '%env(default::DATABASE_USER)%'
        password: '%env(default::DATABASE_PASSWORD)%'
        driver: 'pdo_pgsql'";

        $updatedContent = str_replace($oldConfig, $newConfig, $doctrineContent);

        if ($updatedContent !== $doctrineContent) {
            file_put_contents($doctrinePath, $updatedContent);
            echo "Updated config/packages/doctrine.yaml for Docker environment\n";
        }
    }

    public function configureMailer(): void
    {
        $projectDir = $this->composer->getProjectDir();
        $envPath = $projectDir . '/.env';

        if (!file_exists($envPath)) {
            return; // Skip if .env doesn't exist
        }

        $envContent = file_get_contents($envPath);

        // Check if MAILER_DSN exists and update it to use MailPit
        if (str_contains($envContent, 'MAILER_DSN=null://null')) {
            $updatedContent = str_replace('MAILER_DSN=null://null', 'MAILER_DSN=smtp://mailpit:1025', $envContent);

            if ($updatedContent !== $envContent) {
                file_put_contents($envPath, $updatedContent);
                echo "Updated .env MAILER_DSN for MailPit integration\n";
            }
        }
    }
}