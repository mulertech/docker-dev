<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;

class PhpStanCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'phpstan';
    }

    public function getDefaultArgs(): array
    {
        return ['./vendor/bin/phpstan', 'analyse', '--memory-limit=1G'];
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