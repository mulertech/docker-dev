<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class CsFixerCommand
 * @package MulerTech\DockerDev
 */
class CsFixerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'cs-fixer';
    }

    public function getDefaultArgs(): array
    {
        return ['fix', 'src'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $cmd = 'docker exec -it ' . $containerName . './vendor/bin/php-cs-fixer' . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        $output = shell_exec($cmd);
        echo $output;
    }
}