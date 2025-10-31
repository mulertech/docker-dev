<?php

namespace MulerTech\DockerDev\Command;

/**
 * Interface CommandInterface
 * @package MulerTech\DockerDev
 */
interface CommandInterface
{
    public function getName(): string;
    public function getDefaultArgs(): array;
    public function execute(array $customArgs = []): void;
    public function requiresDocker(): bool;
}