<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class PhpStanCommand
 * @package MulerTech\DockerDev
 */
class PhpStanCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'phpstan';
    }

    public function getDefaultArgs(): array
    {
        return ['analyse', '--memory-limit=1G'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $cmd = 'docker exec ' . $ttyFlag . $containerName . ' ./vendor/bin/phpstan' . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        $output = shell_exec($cmd);
        echo $output;
    }
}