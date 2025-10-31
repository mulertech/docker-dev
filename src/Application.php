<?php

namespace MulerTech\DockerDev;

use MulerTech\DockerDev\Command\CommandRegistry;

class Application
{
    private Composer $composer;
    private Docker $docker;
    private CommandRegistry $commandRegistry;

    public function __construct()
    {
        $this->composer = new Composer();
        $this->docker = new Docker($this->composer);
        $this->commandRegistry = new CommandRegistry($this->docker, $this->composer);
    }

    public function run(array $args): void
    {
        $arg1 = $args[1] ?? '';
        $arg2 = $args[2] ?? '';

        if ($arg1 === 'test-coverage') {
            $this->commandRegistry->executeCommand('test', ['--coverage-html', './.phpunit.cache/coverage']);
        } elseif ($arg1 === 'test') {
            $this->commandRegistry->executeCommand('test');
        } elseif ($arg1 === 'up') {
            $this->docker->dockerComposeUp($arg2);
        } elseif ($arg1 === 'down') {
            $this->docker->dockerComposeDown();
        } elseif ($arg1 === 'phpstan') {
            $this->commandRegistry->executeCommand('phpstan');
        } elseif ($arg1 === 'cs-fixer') {
            $this->commandRegistry->executeCommand('cs-fixer');
        } elseif ($arg1 === 'all') {
            $this->commandRegistry->executeAll();
        } elseif ($arg1 === 'ps') {
            $this->docker->ensureEnvironment();
            $containerName = $this->docker->getContainerName();
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
        } elseif ($arg1 === 'name') {
            $projectName = $this->docker->getProjectName();
            echo "Project name : $projectName" . PHP_EOL . "Configuration CLI interpreter environment variable : COMPOSE_PROJECT_NAME=$projectName" . PHP_EOL;
        } elseif ($arg1 === 'init') {
            $this->initTemplate($arg2);
        } elseif ($arg1 === 'symfony') {
            // Get command arguments starting from index 2 (after 'symfony')
            $consoleArgs = array_slice($args, 2);
            $this->commandRegistry->executeCommand('symfony', $consoleArgs);
        } elseif ($arg1 === 'link') {
            $this->docker->ensureEnvironment();
            $this->docker->displayApacheLink();
        }
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