<?php

namespace MulerTech\DockerDev;

/**
 * Class Symfony.
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
        $doctrinePath = $projectDir.'/config/packages/doctrine.yaml';

        if (!file_exists($doctrinePath)) {
            return; // Skip if doctrine.yaml doesn't exist
        }

        $doctrineContent = (string) file_get_contents($doctrinePath);

        // Check if already modified (avoid duplicate modifications)
        if (str_contains($doctrineContent, '# Modified by mulertech/docker-dev package for Docker environment')) {
            return; // Already configured
        }

        // Replace the DATABASE_URL configuration with flexible fallback configuration
        $oldConfig = "url: '%env(resolve:DATABASE_URL)%'";
        $newConfig = "# Modified by mulertech/docker-dev package for Docker environment
        host: '%env(default::DATABASE_HOST)%'
        port: '%env(default::DATABASE_PORT)%'
        dbname: '%env(default::DATABASE_NAME)%'
        user: '%env(default::DATABASE_USER)%'
        password: '%env(trim:file:DATABASE_PASSWORD_FILE)%'
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
        $mailerPath = $projectDir.'/config/packages/mailer.yaml';

        if (!file_exists($mailerPath)) {
            return; // Skip if mailer.yaml doesn't exist
        }

        $mailerContent = (string) file_get_contents($mailerPath);

        // Check if already modified (avoid duplicate modifications)
        if (str_contains($mailerContent, '# Modified by mulertech/docker-dev package for Docker environment')) {
            return; // Already configured
        }

        // Replace the MAILER_DSN env var with file-based secret
        $oldConfig = "dsn: '%env(MAILER_DSN)%'";
        $newConfig = "# Modified by mulertech/docker-dev package for Docker environment
            dsn: '%env(trim:file:MAILER_DSN_FILE)%'";

        $updatedContent = str_replace($oldConfig, $newConfig, $mailerContent);

        if ($updatedContent !== $mailerContent) {
            file_put_contents($mailerPath, $updatedContent);
            echo "Updated config/packages/mailer.yaml for Docker environment\n";
        }
    }
}
