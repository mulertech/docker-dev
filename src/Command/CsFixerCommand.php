<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;

class CsFixerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'cs-fixer';
    }

    public function getDefaultArgs(): array
    {
        return ['./vendor/bin/php-cs-fixer', 'fix', 'src'];
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