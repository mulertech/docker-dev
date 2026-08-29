<?php

namespace MulerTech\DockerDev\Command;

class ComposerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'composer';
    }

    /** @return array<string> */
    public function getDefaultArgs(): array
    {
        return ['list'];
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
        $cmd = 'docker exec '.$ttyFlag.$containerName.' composer '.$this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd, $exitCode);

        return $exitCode;
    }
}
