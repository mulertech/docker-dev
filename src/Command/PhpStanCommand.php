<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class PhpStanCommand.
 */
class PhpStanCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'phpstan';
    }

    /** @return array<string> */
    public function getDefaultArgs(): array
    {
        return ['analyse', '--memory-limit=1G'];
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
        $cmd = 'docker exec '.$ttyFlag.$containerName.' ./vendor/bin/phpstan '.$this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd, $exitCode);

        return $exitCode;
    }
}
