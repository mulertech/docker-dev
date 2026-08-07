<?php

namespace MulerTech\DockerDev;

use MulerTech\DockerDev\Command\CommandRegistry;
use MulerTech\DockerDev\Command\SandboxCommand;

/**
 * Class Application.
 */
class Application
{
    private Docker $docker;
    private Composer $composer;
    private CommandRegistry $commandRegistry;

    public function __construct()
    {
        $this->composer = new Composer();
        $this->docker = new Docker($this->composer);
        $this->commandRegistry = new CommandRegistry($this->docker, $this->composer);
    }

    /** @param array<string> $args */
    public function run(array $args): void
    {
        $arg1 = $args[1] ?? '';
        $arg2 = $args[2] ?? '';

        switch ($arg1) {
            case 'test-coverage':
                $this->handleTestCoverage();
                break;
            case 'test-coverage-ai':
                $this->handleTestCoverageAi();
                break;
            case 'test-ai':
                $this->handleTestAi($args);
                break;
            case 'test':
                $this->handleTest($args);
                break;
            case 'up':
                $this->handleUp($arg2);
                break;
            case 'down':
                $this->handleDown();
                break;
            case 'phpstan-ai':
                $this->handlePhpstanAi($args);
                break;
            case 'phpstan':
                $this->handlePhpstan($args);
                break;
            case 'cs-fixer-ai':
                $this->handleCsFixerAi($args);
                break;
            case 'cs-fixer':
                $this->handleCsFixer($args);
                break;
            case 'all-ai':
                $this->handleAllAi();
                break;
            case 'all':
                $this->handleAll();
                break;
            case 'ps-ai':
                $this->handlePsAi();
                break;
            case 'ps':
                $this->handlePs();
                break;
            case 'name':
                $this->handleName();
                break;
            case 'init':
                $this->handleInit($arg2);
                break;
            case 'modules':
                $this->handleModules();
                break;
            case 'symfony':
                $this->handleSymfony($args);
                break;
            case 'composer':
                $this->handleComposer($args);
                break;
            case 'link':
                $this->handleLink();
                break;
            case 'sandbox':
                $this->handleSandbox();
                break;
        }
    }

    private function handleTestCoverage(): void
    {
        $this->commandRegistry->executeCommand('test', ['--coverage-html', './.phpunit.cache/coverage']);
    }

    private function enableQuietMode(): void
    {
        $this->docker->setQuiet(true);
    }

    private function handleTestCoverageAi(): void
    {
        $this->enableQuietMode();
        $this->commandRegistry->executeCommand('test', [
            '--coverage-text', '--colors=never', '--no-progress',
        ]);
    }

    /** @param array<string> $args */
    private function handleTestAi(array $args): void
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['--no-progress', '--colors=never'];
        $this->commandRegistry->executeCommand('test', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handleTest(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('test', $consoleArgs);
    }

    private function handleUp(string $arg2): void
    {
        $this->docker->dockerComposeUp($arg2);
    }

    private function handleDown(): void
    {
        $this->docker->dockerComposeDown();
    }

    /** @param array<string> $args */
    private function handlePhpstanAi(array $args): void
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['analyse', '--memory-limit=1G', '--no-progress', '--error-format=json'];
        $this->commandRegistry->executeCommand('phpstan', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handlePhpstan(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('phpstan', $consoleArgs);
    }

    /** @param array<string> $args */
    private function handleCsFixerAi(array $args): void
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['fix', 'src', '--format=json', '--no-ansi', '--show-progress=none'];
        $this->commandRegistry->executeCommand('cs-fixer', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handleCsFixer(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('cs-fixer', $consoleArgs);
    }

    private function handleAllAi(): void
    {
        $this->enableQuietMode();
        $this->handleCsFixerAi([]);
        $this->handleTestAi([]);
        $this->handlePhpstanAi([]);
        $this->handleSchemaValidateAi();
        $this->handleAuditAi();
    }

    private function handleAuditAi(): void
    {
        $this->enableQuietMode();
        $this->commandRegistry->executeCommand('composer', [
            'audit', '--format=summary', '--no-interaction', '--no-ansi',
        ]);
    }

    private function handleSchemaValidateAi(): void
    {
        if (!$this->composer->isSymfonyProject()) {
            return;
        }

        if (!$this->composer->hasPackage('doctrine/orm') && !$this->composer->hasPackage('doctrine/doctrine-bundle')) {
            return;
        }

        $this->enableQuietMode();
        $this->commandRegistry->executeCommand('symfony', [
            'doctrine:schema:validate', '--env=test', '--no-interaction', '--no-ansi',
        ]);
    }

    private function handleAll(): void
    {
        $this->commandRegistry->executeAll();
    }

    private function handlePsAi(): void
    {
        $this->enableQuietMode();
        $this->docker->ensureEnvironment();
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp) {
            $this->docker->dockerComposeUp('-d');
        }

        $cmd = $this->docker->dockerComposeCommand().' ps --format json';
        $output = shell_exec($cmd);
        echo $output;

        if (!$dockerUp) {
            $this->docker->dockerComposeDown();
        }
    }

    private function handlePs(): void
    {
        $this->docker->ensureEnvironment();
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp) {
            $this->docker->dockerComposeUp('-d');
        }

        $cmd = $this->docker->dockerComposeCommand().' ps';
        $output = shell_exec($cmd);
        echo $output;

        if (!$dockerUp) {
            $this->docker->dockerComposeDown();
        }
    }

    private function handleName(): void
    {
        $projectName = $this->docker->getProjectName();
        echo "Project name : $projectName".PHP_EOL."Configuration CLI interpreter environment variable : COMPOSE_PROJECT_NAME=$projectName".PHP_EOL;
    }

    private function handleInit(string $modulesArg): void
    {
        if (empty($modulesArg)) {
            $this->docker->autoInitModules(true);

            return;
        }

        $modules = array_map('trim', explode(',', $modulesArg));
        $this->docker->performModuleInitialization($modules, true);
    }

    private function handleModules(): void
    {
        $modules = $this->docker->loadModuleConfig();

        if ([] === $modules) {
            echo "No modules configured. Run 'mtdocker init' to initialize.\n";

            return;
        }

        echo "Active modules:\n";
        foreach ($modules as $module) {
            echo "  - $module\n";
        }
    }

    /** @param array<string> $args */
    private function handleSymfony(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('symfony', $consoleArgs);
    }

    /** @param array<string> $args */
    private function handleComposer(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('composer', $consoleArgs);
    }

    private function handleLink(): void
    {
        $this->docker->ensureEnvironment();
        $this->docker->displayWebLink();
    }

    private function handleSandbox(): void
    {
        $sandboxCommand = new SandboxCommand($this->docker);
        $sandboxCommand->execute();
    }
}
