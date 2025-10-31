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
            case 'test':
                $this->handleTest($args);
                break;
            case 'up':
                $this->handleUp($arg2);
                break;
            case 'down':
                $this->handleDown();
                break;
            case 'phpstan':
                $this->handlePhpstan($args);
                break;
            case 'cs-fixer':
                $this->handleCsFixer($args);
                break;
            case 'all':
                $this->handleAll();
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
            case 'symfony':
                $this->handleSymfony($args);
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

    private function handlePhpstan(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('phpstan', $consoleArgs);
    }

    private function handleCsFixer(array $args): void
    {
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('cs-fixer', $consoleArgs);
    }

    private function handleAll(): void
    {
        $this->commandRegistry->executeAll();
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

    private function handleInit(string $template): void
    {
        $this->initTemplate($template);
    }

    private function handleSymfony(array $args): void
    {
        // Get command arguments starting from index 2 (after 'symfony')
        $consoleArgs = array_slice($args, 2);
        $this->commandRegistry->executeCommand('symfony', $consoleArgs);
    }

    private function handleLink(): void
    {
        $this->docker->ensureEnvironment();
        $this->docker->displayApacheLink();
    }

    private function initTemplate(string $template): void
    {
        // Auto-detect template if not specified
        if (empty($template)) {
            $template = $this->docker->detectTemplate();
            echo "Auto-detected template: $template\n";
        }

        // Use the common initialization function with confirmation
        $this->docker->performTemplateInitialization($template, true);
    }
}