<?php

namespace MulerTech\DockerDev;

class Composer
{
    public function getProjectDir(): string
    {
        $projectDir = dirname(__DIR__, 1);

        while (!file_exists($projectDir . '/composer.json')) {
            $projectDir = dirname($projectDir);
        }

        return $projectDir;
    }

    public function getPhpVersion(): string
    {
        $composer = file_get_contents($this->getProjectDir() . '/composer.json');
        preg_match('/"php": "(.+)"/', $composer, $matches);
        $php = $matches[1] ?? '';

        if ($php === '') {
            return '';
        }

        // Extract PHP version from various constraint formats: ^8.3, ~8.2, >=8.4, 8.1.*, etc.
        preg_match('/(\d+\.\d+)/', $php, $matches);
        return $matches[1] ?? '';
    }

    public function dbNeeded(): bool
    {
        $composer = file_get_contents($this->getProjectDir() . '/composer.json');
        return str_contains($composer, 'ext-pdo');
    }

    public function isSymfonyProject(): bool
    {
        $composer = file_get_contents($this->getProjectDir() . '/composer.json');
        return str_contains($composer, 'symfony/framework-bundle')
            || str_contains($composer, 'symfony/symfony')
            || str_contains($composer, 'symfony/kernel');
    }
}