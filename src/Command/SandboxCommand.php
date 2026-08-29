<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;

class SandboxCommand
{
    private Docker $docker;

    public function __construct(Docker $docker)
    {
        $this->docker = $docker;
    }

    public function execute(): int
    {
        $this->docker->ensureSandboxEnvironment();

        $wasUp = $this->docker->isSandboxUp();

        if (!$wasUp) {
            $this->docker->sandboxUp();
        }

        $containerName = $this->docker->getSandboxContainerName();
        $ttyFlag = (posix_isatty(STDIN)) ? '-it ' : '-i ';
        $cmd = 'docker exec '.$ttyFlag.escapeshellarg($containerName).' php /sandbox/sandbox.php';
        passthru($cmd, $exitCode);

        if (!$wasUp) {
            $this->docker->sandboxDown();
        }

        return $exitCode;
    }
}
