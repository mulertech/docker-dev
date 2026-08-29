<?php

namespace MulerTech\DockerDev\Command;

/**
 * Interface CommandInterface.
 */
interface CommandInterface
{
    public function getName(): string;

    /** @return array<string> */
    public function getDefaultArgs(): array;

    /**
     * @param array<string> $customArgs
     *
     * @return int the exit code of the underlying process, so a caller chaining several
     *             commands can tell a failure from a success
     */
    public function execute(array $customArgs = []): int;

    public function requiresDocker(): bool;
}
