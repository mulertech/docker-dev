<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Docker;
use MulerTech\DockerDev\Composer;

class CommandRegistry
{
    private array $commands = [];

    public function __construct(Docker $docker, Composer $composer)
    {
        $this->registerCommand(new PhpunitCommand($docker));
        $this->registerCommand(new PhpStanCommand($docker));
        $this->registerCommand(new CsFixerCommand($docker));
        $this->registerCommand(new SymfonyCommand($docker, $composer));
    }

    public function registerCommand(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function executeCommand(string $name, array $args = []): void
    {
        if (!$this->hasCommand($name)) {
            echo "Unknown command: $name\n";
            return;
        }

        $this->commands[$name]->execute($args);
    }

    public function executeAll(): void
    {
        $this->commands['cs-fixer']->execute();
        $this->commands['test']->execute();
        $this->commands['phpstan']->execute();
    }
}