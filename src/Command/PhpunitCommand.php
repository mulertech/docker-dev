<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class PhpunitCommand
 * @package MulerTech\DockerDev
 */
class PhpunitCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'test';
    }

    public function getDefaultArgs(): array
    {
        return [];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $cmd = 'docker exec -it ' . $containerName . ' ./vendor/bin/phpunit' . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        $output = shell_exec($cmd);
        echo $output;
    }
}