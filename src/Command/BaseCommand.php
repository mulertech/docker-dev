<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;

abstract class BaseCommand implements CommandInterface
{
    protected Docker $docker;

    public function __construct(Docker $docker)
    {
        $this->docker = $docker;
    }

    /** @param array<string> $customArgs */
    public function execute(array $customArgs = []): void
    {
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp && $this->requiresDocker()) {
            $this->docker->dockerComposeUp('-d');
        }

        $this->docker->runFirstTimeSetup();

        $this->runCommand($customArgs);

        if (!$dockerUp && $this->requiresDocker()) {
            $this->docker->dockerComposeDown();
        }
    }

    /** @param array<string> $customArgs */
    abstract protected function runCommand(array $customArgs = []): void;

    /**
     * @param array<string> $defaultArgs
     * @param array<string> $customArgs
     */
    protected function buildCommand(array $defaultArgs, array $customArgs = []): string
    {
        $allArgs = [] !== $customArgs ? $customArgs : $defaultArgs;

        return implode(' ', array_map('escapeshellarg', $allArgs));
    }
}
