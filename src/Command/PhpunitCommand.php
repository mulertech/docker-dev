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
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $envFlags = $this->needsCoverage($customArgs) ? '-e XDEBUG_MODE=coverage ' : '';
        $cmd = 'docker exec ' . $envFlags . $ttyFlag . $containerName . ' ./vendor/bin/phpunit' . ' ' . $this->buildCommand($this->getDefaultArgs(), $customArgs);
        $output = shell_exec($cmd);
        echo $output;
    }

    private function needsCoverage(array $args): bool
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--coverage-')) {
                return true;
            }
        }
        return false;
    }
}