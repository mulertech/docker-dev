<?php

namespace MulerTech\DockerDev\Command;

/**
 * Class CsFixerCommand.
 */
class CsFixerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'cs-fixer';
    }

    /** @return array<string> */
    public function getDefaultArgs(): array
    {
        return ['fix', 'src'];
    }

    public function requiresDocker(): bool
    {
        return true;
    }

    /** @param array<string> $customArgs */
    protected function runCommand(array $customArgs = []): void
    {
        $containerName = $this->docker->getContainerName();
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $cmd = 'docker exec '.$ttyFlag.$containerName.' ./vendor/bin/php-cs-fixer '.$this->buildCommand($this->getDefaultArgs(), $customArgs);
        passthru($cmd);
    }
}
