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

    public function execute(array $customArgs = []): void
    {
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp && $this->requiresDocker()) {
            $this->docker->dockerComposeUp('-d');
        }

        $this->runCommand($customArgs);

        if (!$dockerUp && $this->requiresDocker()) {
            $this->docker->dockerComposeDown();
        }
    }

    abstract protected function runCommand(array $customArgs = []): void;

    protected function buildCommand(array $defaultArgs, array $customArgs = []): string
    {
        $allArgs = $customArgs !== [] ? $customArgs : $defaultArgs;
        return implode(' ', array_map('escapeshellarg', $allArgs));
    }
}