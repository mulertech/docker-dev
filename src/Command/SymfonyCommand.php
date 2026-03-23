<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Composer;
use MulerTech\DockerDev\Docker;

/**
 * Class SymfonyCommand.
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

    /** @return array<string> */
    public function getDefaultArgs(): array
    {
        return ['list'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    /** @param array<string> $customArgs */
    public function execute(array $customArgs = []): void
    {
        if (!$this->composer->isSymfonyProject()) {
            echo "Error: This command is only available for Symfony projects.\n";

            return;
        }

        parent::execute($customArgs);
    }

    /** @param array<string> $customArgs */
    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $cmd = 'docker exec '.$ttyFlag.$containerName.' php bin/console '.$this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd);
    }
}
