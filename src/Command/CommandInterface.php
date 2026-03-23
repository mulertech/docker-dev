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

    /** @param array<string> $customArgs */
    public function execute(array $customArgs = []): void;

    public function requiresDocker(): bool;
}
