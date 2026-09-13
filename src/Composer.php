<?php

namespace MulerTech\DockerDev;

/**
 * Class Composer.
 */
class Composer
{
    public function getProjectDir(): string
    {
        $projectDir = (string) getcwd();

        while (!file_exists($projectDir.'/composer.json')) {
            $projectDir = dirname($projectDir);
        }

        return $projectDir;
    }

    public function getPhpVersion(): string
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');
        preg_match('/"php": "(.+)"/', $composer, $matches);
        $php = $matches[1] ?? '';

        if ('' === $php) {
            return '';
        }

        // Extract PHP version from various constraint formats: ^8.3, ~8.2, >=8.4, 8.1.*, etc.
        preg_match('/(\d+\.\d+)/', $php, $matches);

        return $matches[1] ?? '';
    }

    public function dbNeeded(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'ext-pdo');
    }

    public function isSymfonyProject(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'symfony/framework-bundle')
            || str_contains($composer, 'symfony/symfony')
            || str_contains($composer, 'symfony/kernel');
    }

    public function needsPgvectorAndOllama(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'pgvector')
            || str_contains($composer, 'openai')
            || str_contains($composer, 'anthropic')
            || str_contains($composer, 'langchain')
            || str_contains($composer, 'chromadb')
            || str_contains($composer, 'yethee/tiktoken');
    }

    public function needsPostgis(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'longitude-one/doctrine-spatial')
            || str_contains($composer, 'jsor/doctrine-postgis')
            || str_contains($composer, 'postgis');
    }

    public function needsWkhtmltopdf(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'knplabs/knp-snappy-bundle');
    }

    public function needsGotenberg(): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, 'sensiolabs/gotenberg-bundle');
    }

    public function hasPackage(string $package): bool
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');

        return str_contains($composer, $package);
    }

    public function hasFile(string $relativePath): bool
    {
        return file_exists($this->getProjectDir().DIRECTORY_SEPARATOR.$relativePath);
    }

    /**
     * Modules the project opts into explicitly via composer.json
     * `extra.mtdocker.modules` (e.g. ["valhalla"]). Lets a project request a
     * bespoke service that has no detectable composer dependency, without
     * polluting auto-detection for unrelated projects.
     *
     * @return array<string>
     */
    public function extraModules(): array
    {
        $composer = (string) file_get_contents($this->getProjectDir().'/composer.json');
        $data = json_decode($composer, true);

        $extra = is_array($data) ? ($data['extra'] ?? null) : null;
        $mtdocker = is_array($extra) ? ($extra['mtdocker'] ?? null) : null;
        $modules = is_array($mtdocker) ? ($mtdocker['modules'] ?? null) : null;

        if (!is_array($modules)) {
            return [];
        }

        return array_values(array_filter($modules, 'is_string'));
    }

    /**
     * PHP extensions the project declares as ext-* in require and require-dev, sorted and named
     * the way install-php-extensions names them. The web image installs exactly these.
     *
     * @return array<string>
     */
    public function requiredExtensions(): array
    {
        $data = json_decode((string) file_get_contents($this->getProjectDir().'/composer.json'), true);
        $extensions = [];

        foreach (['require', 'require-dev'] as $section) {
            $requirements = is_array($data) ? ($data[$section] ?? null) : null;

            foreach (is_array($requirements) ? array_keys($requirements) : [] as $package) {
                if (str_starts_with((string) $package, 'ext-')) {
                    $extensions[] = self::normalizeExtensionName(substr((string) $package, 4));
                }
            }
        }

        $extensions = array_values(array_unique($extensions));
        sort($extensions);

        return $extensions;
    }

    /**
     * One spelling for an extension whether it comes from composer.json (`ext-zend-opcache`) or
     * from `php -m` (`Zend OPcache`), matching the name install-php-extensions accepts.
     */
    public static function normalizeExtensionName(string $name): string
    {
        $name = str_replace(' ', '-', strtolower(trim($name)));

        return 'zend-opcache' === $name ? 'opcache' : $name;
    }
}
