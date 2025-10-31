<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;

class PhpunitCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'test';
    }

    public function getDefaultArgs(): array
    {
        return ['./vendor/bin/phpunit'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $cmd = 'docker exec -it ' . $containerName . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        $output = shell_exec($cmd);
        echo $output;
    }
}