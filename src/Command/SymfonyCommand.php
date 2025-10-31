<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;
use MulerTech\DockerDev\Composer;

/**
 * Class SymfonyCommand
 * @package MulerTech\DockerDev
 */
class SymfonyCommand extends BaseCommand
{
    private Composer $composer;

    public function __construct(Docker $docker, Composer $composer)
    {
        parent::__construct($docker);
        $this->composer = $composer;
    }

    public function getName(): string
    {
        return 'symfony';
    }

    public function getDefaultArgs(): array
    {
        return ['list'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    public function execute(array $customArgs = []): void
    {
        if (!$this->composer->isSymfonyProject()) {
            echo "Error: This command is only available for Symfony projects.\n";
            return;
        }

        parent::execute($customArgs);
    }

    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $cmd = 'docker exec -it ' . $containerName . ' php bin/console' . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd);
    }
}