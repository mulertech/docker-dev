<?php

namespace MulerTech\DockerDev\Command;

use MulerTech\DockerDev\Composer;
use MulerTech\DockerDev\Docker;

/**
 * Class CommandRegistry.
 */
class CommandRegistry
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function __construct(Docker $docker, Composer $composer)
    {
        $this->registerCommand(new PhpunitCommand($docker));
        $this->registerCommand(new PhpStanCommand($docker));
        $this->registerCommand(new CsFixerCommand($docker));
        $this->registerCommand(new ComposerCommand($docker));
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

    /** @param array<string> $args */
    public function executeCommand(string $name, array $args = []): int
    {
        if (!$this->hasCommand($name)) {
            echo "Unknown command: $name\n";

            return 1;
        }

        return $this->commands[$name]->execute($args);
    }

    /**
     * Toutes les étapes tournent, même après un échec : on veut la liste complète des
     * problèmes, pas le premier. Le code renvoyé est celui de la première qui a échoué.
     */
    public function executeAll(): int
    {
        $exitCode = 0;

        foreach (['cs-fixer', 'test', 'phpstan'] as $name) {
            $code = $this->commands[$name]->execute();
            $exitCode = 0 !== $exitCode ? $exitCode : $code;
        }

        return $exitCode;
    }
}
