<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class PhpunitCommand.
 */
class PhpunitCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'test';
    }

    /** @return array<string> */
    public function getDefaultArgs(): array
    {
        return [];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    /** @param array<string> $customArgs */
    protected function runCommand(array $customArgs = []): int
    {
        $containerName = $this->docker->getContainerName();
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $cmd = 'docker exec '.$ttyFlag.$containerName.' ./vendor/bin/phpunit '.$this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd, $exitCode);

        return $exitCode;
    }
}
