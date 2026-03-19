<?php

namespace MulerTech\DockerDev;

use MulerTech\DockerDev\Command\CommandRegistry;

/**
 * Class Application
 * @package MulerTech\DockerDev
 */
class Application
{
    private Docker $docker;
    private CommandRegistry $commandRegistry;

    public function __construct()
    {
        $composer = new Composer();
        $this->docker = new Docker($composer);
        $this->commandRegistry = new CommandRegistry($this->docker, $composer);
    }

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
        }
    }

    private function handleTestCoverage(): void
    {
        $this->commandRegistry->executeCommand('test', ['--coverage-html', './.phpunit.cache/coverage']);
    }

    private function handleTestCoverageAi(): void
    {
        $this->commandRegistry->executeCommand('test', [
            '--coverage-text', '--colors=never', '--no-progress',
        ]);
    }

    private function handleTestAi(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['--no-progress', '--colors=never'];
        $this->commandRegistry->executeCommand('test', array_merge($aiArgs, $consoleArgs));
    }

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

    private function handlePhpstanAi(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['analyse', '--memory-limit=1G', '--no-progress', '--error-format=json'];
        $this->commandRegistry->executeCommand('phpstan', array_merge($aiArgs, $consoleArgs));
    }

    private function handlePhpstan(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('phpstan', $consoleArgs);
    }

    private function handleCsFixerAi(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['fix', 'src', '--format=json', '--no-ansi', '--show-progress=none'];
        $this->commandRegistry->executeCommand('cs-fixer', array_merge($aiArgs, $consoleArgs));
    }

    private function handleCsFixer(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('cs-fixer', $consoleArgs);
    }

    private function handleAllAi(): void
    {
        $this->handleCsFixerAi([]);
        $this->handleTestAi([]);
        $this->handlePhpstanAi([]);
    }

    private function handleAll(): void
    {
        $this->commandRegistry->executeAll();
    }

    private function handlePsAi(): void
    {
        $this->docker->ensureEnvironment();
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp) {
            $this->docker->dockerComposeUp('-d');
        }

        $cmd = $this->docker->dockerComposeCommand() . ' ps --format json';
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

        $cmd = $this->docker->dockerComposeCommand() . ' ps';
        $output = shell_exec($cmd);
        echo $output;

        if (!$dockerUp) {
            $this->docker->dockerComposeDown();
        }
    }

    private function handleName(): void
    {
        $projectName = $this->docker->getProjectName();
        echo "Project name : $projectName" . PHP_EOL . "Configuration CLI interpreter environment variable : COMPOSE_PROJECT_NAME=$projectName" . PHP_EOL;
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

        if ($modules === []) {
            echo "No modules configured. Run 'mtdocker init' to initialize.\n";
            return;
        }

        echo "Active modules:\n";
        foreach ($modules as $module) {
            echo "  - $module\n";
        }
    }

    private function handleSymfony(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('symfony', $consoleArgs);
    }

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
}
