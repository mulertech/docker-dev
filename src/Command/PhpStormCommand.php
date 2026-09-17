<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Composer;
use MulerTech\DockerDev\Docker;

/**
 * Points PhpStorm at the project's Docker image: PHP interpreter, language level and PHPUnit,
 * written straight into the project's `.idea` files.
 */
class PhpStormCommand
{
    /**
     * Container path per base module, each taken from the WORKDIR of the image that module builds.
     * PhpStorm starts its own container from the image, so this path has to match the one the
     * compose stack mounts, otherwise a path read in the IDE points at a file that is not there.
     */
    private const array CONTAINER_PATHS = [
        'frankenphp' => '/app',
        'apache-php' => '/var/www/html',
    ];

    /** Configuration file names, in the order PHPUnit itself resolves them. */
    private const array PHPUNIT_CONFIGS = ['phpunit.dist.xml', 'phpunit.xml.dist', 'phpunit.xml'];

    /**
     * Quality tools run through the interpreter, keyed by the binary name in `vendor/bin`:
     * the `php.xml` component, its settings element and the per-interpreter entry holding the path.
     * PhpStorm fills that path with its own mount point, `/opt/project`, which the container
     * never has, so every inspection run fails with "no such file or directory".
     */
    private const array QUALITY_TOOLS = [
        'phpstan' => ['PhpStan', 'PhpStan_settings', 'phpstan_by_interpreter'],
        'php-cs-fixer' => ['PhpCSFixer', 'phpcsfixer_settings', 'phpcs_fixer_by_interpreter'],
    ];

    /** Name of the Docker server the interpreter is attached to, as PhpStorm registers it. */
    private const string DOCKER_ACCOUNT = 'Docker';

    public function __construct(
        private readonly Docker $docker,
        private readonly Composer $composer,
    ) {
    }

    public function execute(): int
    {
        $projectDir = $this->composer->getProjectDir();

        $modules = $this->docker->loadModuleConfig();
        if ([] === $modules) {
            return $this->fail("No module configured. Run 'mtdocker init' first.");
        }

        $containerPath = $this->containerPath($modules);
        if (null === $containerPath) {
            return $this->fail(sprintf(
                'None of the active modules (%s) carries PHP, so there is no interpreter to declare. Expected one of: %s.',
                implode(', ', $modules),
                implode(', ', array_keys(self::CONTAINER_PATHS)),
            ));
        }

        $image = $this->docker->getWebImage();
        if (!$this->imageExists($image)) {
            return $this->fail(sprintf(
                "Image %s is not built, so PhpStorm would fail to probe the interpreter. Run 'mtdocker up -d' first.",
                $image,
            ));
        }

        $phpunitConfig = $this->phpunitConfig($projectDir);
        if (null === $phpunitConfig) {
            return $this->fail(sprintf(
                'No PHPUnit configuration file in %s. Expected one of: %s.',
                $projectDir,
                implode(', ', self::PHPUNIT_CONFIGS),
            ));
        }

        $languageLevel = $this->composer->getPhpVersion();
        if ('' === $languageLevel) {
            return $this->fail(sprintf(
                'No PHP version in %s/composer.json, so the language level cannot be set. Add a "php" constraint to require.',
                $projectDir,
            ));
        }

        $qualityTools = $this->installedQualityTools($projectDir);

        $ideaDir = $projectDir.DIRECTORY_SEPARATOR.'.idea';
        if (!is_dir($ideaDir) && !mkdir($ideaDir, 0o775, true)) {
            return $this->fail(sprintf('Unable to create %s.', $ideaDir));
        }

        $phpXmlPath = $ideaDir.DIRECTORY_SEPARATOR.'php.xml';
        $interpreterId = $this->interpreterId($phpXmlPath, $image) ?? $this->uuid();

        $written = $this->writePhpXml($phpXmlPath, $image, $interpreterId, $languageLevel, $containerPath, $phpunitConfig, $qualityTools)
            && $this->writeDockerSettings($ideaDir.DIRECTORY_SEPARATOR.'php-docker-settings.xml', $interpreterId, $containerPath)
            && $this->writeWorkspace($ideaDir.DIRECTORY_SEPARATOR.'workspace.xml', $image);

        if (!$written) {
            return 1;
        }

        $this->report($image, $containerPath, $phpunitConfig, $languageLevel, $qualityTools);

        return 0;
    }

    /** @param array<string> $modules */
    private function containerPath(array $modules): ?string
    {
        foreach (self::CONTAINER_PATHS as $module => $path) {
            if (in_array($module, $modules, true)) {
                return $path;
            }
        }

        return null;
    }

    private function imageExists(string $image): bool
    {
        exec('docker image inspect '.escapeshellarg($image).' 2>/dev/null', $output, $exitCode);

        return 0 === $exitCode;
    }

    private function phpunitConfig(string $projectDir): ?string
    {
        foreach (self::PHPUNIT_CONFIGS as $candidate) {
            if (file_exists($projectDir.DIRECTORY_SEPARATOR.$candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return array<string> */
    private function installedQualityTools(string $projectDir): array
    {
        return array_values(array_filter(
            array_keys(self::QUALITY_TOOLS),
            static fn (string $tool): bool => file_exists($projectDir.'/vendor/bin/'.$tool),
        ));
    }

    /** Reuses the id PhpStorm already gave this interpreter, so running twice declares it once. */
    private function interpreterId(string $phpXmlPath, string $image): ?string
    {
        if (!file_exists($phpXmlPath)) {
            return null;
        }

        $interpreter = $this->findInterpreter($this->loadOrCreate($phpXmlPath), $image);

        return $interpreter?->getAttribute('id') ?: null;
    }

    /** @param array<string> $qualityTools */
    private function writePhpXml(
        string $path,
        string $image,
        string $interpreterId,
        string $languageLevel,
        string $containerPath,
        string $phpunitConfig,
        array $qualityTools,
    ): bool {
        $document = $this->loadOrCreate($path);

        $interpreter = $this->findInterpreter($document, $image) ?? $this->appendInterpreter($document, $image);
        $interpreter->setAttribute('id', $interpreterId);
        $interpreter->setAttribute('home', 'docker://DATA');
        $interpreter->setAttribute('auto', 'false');
        $interpreter->setAttribute('debugger_id', 'php.debugger.XDebug');

        while (null !== $interpreter->firstChild) {
            $interpreter->removeChild($interpreter->firstChild);
        }

        $remoteData = $document->createElement('remote_data');
        $remoteData->setAttribute('INTERPRETER_PATH', 'php');
        $remoteData->setAttribute('HELPERS_PATH', '/opt/.phpstorm_helpers');
        $remoteData->setAttribute('VALID', 'true');
        $remoteData->setAttribute('RUN_AS_ROOT_VIA_SUDO', 'false');
        $remoteData->setAttribute('DOCKER_ACCOUNT_NAME', self::DOCKER_ACCOUNT);
        $remoteData->setAttribute('DOCKER_IMAGE_NAME', $image);
        $remoteData->setAttribute('DOCKER_REMOTE_PROJECT_PATH', $containerPath);
        $interpreter->appendChild($remoteData);

        $this->replaceComponent($document, 'PhpUnit', sprintf(
            '<component name="PhpUnit"><phpunit_settings><phpunit_by_interpreter interpreter_id="%s" configuration_file_path="%s" custom_loader_path="%s" phpunit_phar_path="" use_configuration_file="true" /></phpunit_settings></component>',
            $interpreterId,
            $containerPath.'/'.$phpunitConfig,
            $containerPath.'/vendor/autoload.php',
        ));

        foreach ($qualityTools as $tool) {
            $this->writeQualityTool($document, $tool, $interpreterId, $containerPath);
        }

        $shared = $this->component($document, 'PhpProjectSharedConfiguration')
            ?? $this->appendComponent($document, 'PhpProjectSharedConfiguration');
        $shared->setAttribute('php_language_level', $languageLevel);

        return $this->save($document, $path);
    }

    /**
     * Edits the tool's entry for this interpreter in place, keeping the timeout and the local
     * configuration the IDE stores beside it. The entry becomes the only default one, since
     * PhpStorm runs whichever entry carries the flag.
     */
    private function writeQualityTool(\DOMDocument $document, string $tool, string $interpreterId, string $containerPath): void
    {
        [$componentName, $settingsName, $entryName] = self::QUALITY_TOOLS[$tool];

        $component = $this->component($document, $componentName) ?? $this->appendComponent($document, $componentName);

        $settings = $this->firstElement($document, sprintf('/project/component[@name="%s"]/%s', $componentName, $settingsName));
        if (!$settings instanceof \DOMElement) {
            $settings = $document->createElement($settingsName);
            $component->appendChild($settings);
        }

        $entry = null;
        foreach ($this->elements($document, sprintf('/project/component[@name="%s"]/%s/%s', $componentName, $settingsName, $entryName)) as $candidate) {
            if ($candidate->getAttribute('interpreter_id') === $interpreterId) {
                $entry = $candidate;
            } else {
                $candidate->removeAttribute('asDefaultInterpreter');
            }
        }

        if (null === $entry) {
            $entry = $document->createElement($entryName);
            $settings->appendChild($entry);
        }

        $entry->setAttribute('asDefaultInterpreter', 'true');
        $entry->setAttribute('interpreter_id', $interpreterId);
        $entry->setAttribute('tool_path', $containerPath.'/vendor/bin/'.$tool);
    }

    private function writeDockerSettings(string $path, string $interpreterId, string $containerPath): bool
    {
        $document = $this->loadOrCreate($path);

        $map = $this->firstElement($document, '/project/component[@name="PhpDockerContainerSettings"]/list/map');

        if (!$map instanceof \DOMElement) {
            $list = $document->createElement('list');
            $this->appendComponent($document, 'PhpDockerContainerSettings')->appendChild($list);
            $map = $document->createElement('map');
            $list->appendChild($map);
        }

        foreach ($this->elements($document, sprintf('//entry[@key="%s"]', $interpreterId)) as $previous) {
            $previous->parentNode?->removeChild($previous);
        }

        $entry = $document->createDocumentFragment();
        $entry->appendXML(sprintf(
            '<entry key="%s"><value><DockerContainerSettings><option name="runCliOptions" value="" /><option name="version" value="1" /><option name="volumeBindings"><list><DockerVolumeBindingImpl><option name="containerPath" value="%s" /><option name="hostPath" value="$PROJECT_DIR$" /></DockerVolumeBindingImpl></list></option></DockerContainerSettings></value></entry>',
            $interpreterId,
            $containerPath,
        ));
        $map->appendChild($entry);

        return $this->save($document, $path);
    }

    /**
     * Selects the interpreter for the project.
     *
     * This file is edited in place rather than rebuilt: it belongs to the running IDE, which holds
     * the rest of it in memory and writes that copy back whenever it saves.
     */
    private function writeWorkspace(string $path, string $image): bool
    {
        $component = sprintf('<component name="PhpWorkspaceProjectConfiguration" interpreter_name="%s"', $image);

        if (!file_exists($path)) {
            return $this->put($path, sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>%s<project version="4">%s  %s />%s</project>%s',
                PHP_EOL,
                PHP_EOL,
                $component,
                PHP_EOL,
                PHP_EOL,
            ));
        }

        $content = (string) file_get_contents($path);

        if (str_contains($content, '<component name="PhpWorkspaceProjectConfiguration"')) {
            $updated = (string) preg_replace(
                '/<component name="PhpWorkspaceProjectConfiguration"(\s+interpreter_name="[^"]*")?/',
                $component,
                $content,
                1,
            );
        } else {
            $updated = str_replace('</project>', '  '.$component.' />'.PHP_EOL.'</project>', $content);
        }

        return $this->put($path, $updated);
    }

    private function loadOrCreate(string $path): \DOMDocument
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        if (file_exists($path) && $document->load($path)) {
            return $document;
        }

        $project = $document->createElement('project');
        $project->setAttribute('version', '4');
        $document->appendChild($project);

        return $document;
    }

    private function findInterpreter(\DOMDocument $document, string $image): ?\DOMElement
    {
        return $this->firstElement($document, sprintf(
            '/project/component[@name="PhpInterpreters"]/interpreters/interpreter[@name="%s"]',
            $image,
        ));
    }

    private function appendInterpreter(\DOMDocument $document, string $image): \DOMElement
    {
        $interpreters = $this->firstElement($document, '/project/component[@name="PhpInterpreters"]/interpreters');

        if (!$interpreters instanceof \DOMElement) {
            $interpreters = $document->createElement('interpreters');
            $component = $this->component($document, 'PhpInterpreters')
                ?? $this->appendComponent($document, 'PhpInterpreters');
            $component->appendChild($interpreters);
        }

        $interpreter = $document->createElement('interpreter');
        $interpreter->setAttribute('name', $image);
        $interpreters->appendChild($interpreter);

        return $interpreter;
    }

    private function component(\DOMDocument $document, string $name): ?\DOMElement
    {
        return $this->firstElement($document, sprintf('/project/component[@name="%s"]', $name));
    }

    private function appendComponent(\DOMDocument $document, string $name): \DOMElement
    {
        $component = $document->createElement('component');
        $component->setAttribute('name', $name);
        $document->documentElement?->appendChild($component);

        return $component;
    }

    private function replaceComponent(\DOMDocument $document, string $name, string $xml): void
    {
        $existing = $this->component($document, $name);

        if (null !== $existing) {
            $existing->parentNode?->removeChild($existing);
        }

        $fragment = $document->createDocumentFragment();
        $fragment->appendXML($xml);
        $document->documentElement?->appendChild($fragment);
    }

    private function firstElement(\DOMDocument $document, string $path): ?\DOMElement
    {
        return $this->elements($document, $path)[0] ?? null;
    }

    /** @return array<int, \DOMElement> */
    private function elements(\DOMDocument $document, string $path): array
    {
        $nodes = new \DOMXPath($document)->query($path);
        $elements = [];

        if (false === $nodes) {
            return $elements;
        }

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    private function save(\DOMDocument $document, string $path): bool
    {
        $xml = $document->saveXML();

        if (false === $xml) {
            fwrite(STDERR, sprintf('mtdocker phpstorm: unable to serialize %s.'.PHP_EOL, $path));

            return false;
        }

        return $this->put($path, $xml);
    }

    private function put(string $path, string $content): bool
    {
        if (false === file_put_contents($path, $content)) {
            fwrite(STDERR, sprintf('mtdocker phpstorm: unable to write %s.'.PHP_EOL, $path));

            return false;
        }

        return true;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /** @param array<string> $qualityTools */
    private function report(string $image, string $containerPath, string $phpunitConfig, string $languageLevel, array $qualityTools): void
    {
        $tools = [] === $qualityTools
            ? 'none installed (looked for '.implode(', ', array_map(static fn (string $tool): string => 'vendor/bin/'.$tool, array_keys(self::QUALITY_TOOLS))).')'
            : implode(', ', array_map(static fn (string $tool): string => $containerPath.'/vendor/bin/'.$tool, $qualityTools));

        echo 'PhpStorm configured:'.PHP_EOL
            .'  Interpreter      '.$image.' (Docker server "'.self::DOCKER_ACCOUNT.'")'.PHP_EOL
            .'  Project mounted  '.$containerPath.PHP_EOL
            .'  PHPUnit          '.$containerPath.'/'.$phpunitConfig.PHP_EOL
            .'  Quality tools    '.$tools.PHP_EOL
            .'  Language level   '.$languageLevel.PHP_EOL
            .PHP_EOL
            .'The IDE rereads php.xml on its own. The interpreter selection lives in workspace.xml,'.PHP_EOL
            .'which it rereads only on demand, so with the project open run now:'.PHP_EOL
            .'  File | Reload All from Disk'.PHP_EOL
            .'Quitting instead of reloading writes its own copy of workspace.xml back over the selection.'.PHP_EOL
            .PHP_EOL
            .'An interpreter left without a PHP version means the IDE has no Docker server named "'
            .self::DOCKER_ACCOUNT.'", to be added once in'.PHP_EOL
            .'  Settings | Build, Execution, Deployment | Docker'.PHP_EOL;
    }

    private function fail(string $message): int
    {
        fwrite(STDERR, 'mtdocker phpstorm: '.$message.PHP_EOL);

        return 1;
    }
}
