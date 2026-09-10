<?php

namespace MulerTech\DockerDev;

use MulerTech\DockerDev\Command\CommandRegistry;
use MulerTech\DockerDev\Command\SandboxCommand;

/**
 * Class Application.
 */
class Application
{
    private Docker $docker;
    private Composer $composer;
    private CommandRegistry $commandRegistry;

    public function __construct()
    {
        $this->composer = new Composer();
        $this->docker = new Docker($this->composer);
        $this->commandRegistry = new CommandRegistry($this->docker, $this->composer);
    }

    /**
     * Every command this dispatcher answers to, named in one place.
     *
     * A command added to the switch without being added here is refused the first time it is
     * typed, which is a five-second fix; the reverse, a name listed with no branch to serve it,
     * raises the LogicException below. Neither drift can pass unnoticed, which is the point of
     * keeping the list rather than pointing at the README.
     */
    private const array COMMANDS = [
        'test',
        'test-ai',
        'test-coverage',
        'test-coverage-ai',
        'phpstan',
        'phpstan-ai',
        'cs-fixer',
        'cs-fixer-ai',
        'all',
        'all-ai',
        'up',
        'down',
        'ps',
        'ps-ai',
        'name',
        'init',
        'modules',
        'link',
        'sandbox',
        'symfony',
        'composer',
    ];

    /** @param array<string> $args */
    public function run(array $args): int
    {
        $arg1 = $args[1] ?? '';
        $arg2 = $args[2] ?? '';

        if (!in_array($arg1, self::COMMANDS, true)) {
            return $this->reportUnknownCommand($arg1);
        }

        switch ($arg1) {
            case 'test-coverage':
                return $this->handleTestCoverage();
            case 'test-coverage-ai':
                return $this->handleTestCoverageAi();
            case 'test-ai':
                return $this->handleTestAi($args);
            case 'test':
                return $this->handleTest($args);
            case 'up':
                $this->handleUp($arg2);
                break;
            case 'down':
                $this->handleDown();
                break;
            case 'phpstan-ai':
                return $this->handlePhpstanAi($args);
            case 'phpstan':
                return $this->handlePhpstan($args);
            case 'cs-fixer-ai':
                return $this->handleCsFixerAi($args);
            case 'cs-fixer':
                return $this->handleCsFixer($args);
            case 'all-ai':
                return $this->handleAllAi();
            case 'all':
                return $this->handleAll();
            case 'ps-ai':
                $this->handlePsAi();
                break;
            case 'ps':
                $this->handlePs();
                break;
            case 'name':
                $this->handleName();
                break;
            case 'init':
                $this->handleInit($arg2);
                break;
            case 'modules':
                $this->handleModules();
                break;
            case 'symfony':
                return $this->handleSymfony($args);
            case 'composer':
                return $this->handleComposer($args);
            case 'link':
                $this->handleLink();
                break;
            case 'sandbox':
                return $this->handleSandbox();
            default:
                // Only reachable by editing this file: a name was added to COMMANDS without a
                // branch to serve it. Saying so beats returning success and doing nothing.
                throw new \LogicException(sprintf('Command "%s" is declared but not handled.', $arg1));
        }

        return 0;
    }

    /**
     * Names a mistyped command and lists what would have worked.
     *
     * Without this, an unknown command fell through the switch, exited zero and printed nothing:
     * the shell wrapper relays anything it does not recognise, so a typo looked like a success.
     */
    private function reportUnknownCommand(string $command): int
    {
        $message = '' === $command
            ? "Usage: mtdocker <command>\n"
            : sprintf("Unknown command \"%s\".\n", $command);

        fwrite(STDERR, $message."\nAvailable commands:\n  ".implode("\n  ", self::COMMANDS)."\n");

        return 1;
    }

    private function handleTestCoverage(): int
    {
        return $this->commandRegistry->executeCommand('test', ['--coverage-html', './.phpunit.cache/coverage']);
    }

    private function enableQuietMode(): void
    {
        $this->docker->setQuiet(true);
    }

    private function handleTestCoverageAi(): int
    {
        $this->enableQuietMode();

        return $this->commandRegistry->executeCommand('test', [
            '--coverage-text', '--colors=never', '--no-progress',
        ]);
    }

    /** @param array<string> $args */
    private function handleTestAi(array $args): int
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['--no-progress', '--colors=never'];

        return $this->commandRegistry->executeCommand('test', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handleTest(array $args): int
    {
        $consoleArgs = array_slice($args, 2);

        return $this->commandRegistry->executeCommand('test', $consoleArgs);
    }

    private function handleUp(string $arg2): void
    {
        $this->docker->dockerComposeUp($arg2);
    }

    private function handleDown(): void
    {
        $this->docker->dockerComposeDown();
    }

    /** @param array<string> $args */
    private function handlePhpstanAi(array $args): int
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['analyse', '--memory-limit=1G', '--no-progress', '--error-format=json'];

        return $this->commandRegistry->executeCommand('phpstan', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handlePhpstan(array $args): int
    {
        $consoleArgs = array_slice($args, 2);

        return $this->commandRegistry->executeCommand('phpstan', $consoleArgs);
    }

    /** @param array<string> $args */
    private function handleCsFixerAi(array $args): int
    {
        $this->enableQuietMode();
        $consoleArgs = array_slice($args, 2);
        $aiArgs = ['fix', 'src', '--format=json', '--no-ansi', '--show-progress=none'];

        return $this->commandRegistry->executeCommand('cs-fixer', array_merge($aiArgs, $consoleArgs));
    }

    /** @param array<string> $args */
    private function handleCsFixer(array $args): int
    {
        $consoleArgs = array_slice($args, 2);

        return $this->commandRegistry->executeCommand('cs-fixer', $consoleArgs);
    }

    /**
     * Toutes les étapes tournent, même après un échec : on veut la liste complète des
     * problèmes en une passe. Le code renvoyé est celui de la première qui a échoué, sans
     * quoi une suite de tests rouge passerait pour un succès auprès de tout appelant —
     * chaîne d'intégration comme lecteur pressé.
     */
    private function handleAllAi(): int
    {
        $this->enableQuietMode();

        $exitCode = 0;
        foreach ([
            $this->handleCsFixerAi([]),
            $this->handleTestAi([]),
            $this->handlePhpstanAi([]),
            $this->handleSchemaValidateAi(),
            $this->handleAuditAi(),
        ] as $code) {
            $exitCode = 0 !== $exitCode ? $exitCode : $code;
        }

        return $exitCode;
    }

    private function handleAuditAi(): int
    {
        $this->enableQuietMode();

        return $this->commandRegistry->executeCommand('composer', [
            'audit', '--format=summary', '--no-interaction', '--no-ansi',
        ]);
    }

    private function handleSchemaValidateAi(): int
    {
        if (!$this->composer->isSymfonyProject()) {
            return 0;
        }

        if (!$this->composer->hasPackage('doctrine/orm') && !$this->composer->hasPackage('doctrine/doctrine-bundle')) {
            return 0;
        }

        $this->enableQuietMode();

        return $this->commandRegistry->executeCommand('symfony', [
            'doctrine:schema:validate', '--env=test', '--no-interaction', '--no-ansi',
        ]);
    }

    private function handleAll(): int
    {
        return $this->commandRegistry->executeAll();
    }

    private function handlePsAi(): void
    {
        $this->enableQuietMode();
        $this->docker->ensureEnvironment();
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp) {
            $this->docker->dockerComposeUp('-d');
        }

        $cmd = $this->docker->dockerComposeCommand().' ps --format json';
        $output = shell_exec($cmd);
        echo $output;

        if (!$dockerUp) {
            $this->docker->dockerComposeDown();
        }
    }

    private function handlePs(): void
    {
        $this->docker->ensureEnvironment();
        $dockerUp = $this->docker->isDockerUp();

        if (!$dockerUp) {
            $this->docker->dockerComposeUp('-d');
        }

        $cmd = $this->docker->dockerComposeCommand().' ps';
        $output = shell_exec($cmd);
        echo $output;

        if (!$dockerUp) {
            $this->docker->dockerComposeDown();
        }
    }

    private function handleName(): void
    {
        $projectName = $this->docker->getProjectName();
        echo "Project name : $projectName".PHP_EOL."Configuration CLI interpreter environment variable : COMPOSE_PROJECT_NAME=$projectName".PHP_EOL;
    }

    private function handleInit(string $modulesArg): void
    {
        if (empty($modulesArg)) {
            $this->docker->autoInitModules(true);

            return;
        }

        $modules = array_map('trim', explode(',', $modulesArg));
        $this->docker->performModuleInitialization($modules, true);
    }

    private function handleModules(): void
    {
        $modules = $this->docker->loadModuleConfig();

        if ([] === $modules) {
            echo "No modules configured. Run 'mtdocker init' to initialize.\n";

            return;
        }

        echo "Active modules:\n";
        foreach ($modules as $module) {
            echo "  - $module\n";
        }
    }

    /** @param array<string> $args */
    private function handleSymfony(array $args): int
    {
        $consoleArgs = array_slice($args, 2);

        return $this->commandRegistry->executeCommand('symfony', $consoleArgs);
    }

    /** @param array<string> $args */
    private function handleComposer(array $args): int
    {
        $consoleArgs = array_slice($args, 2);

        return $this->commandRegistry->executeCommand('composer', $consoleArgs);
    }

    private function handleLink(): void
    {
        $this->docker->ensureEnvironment();
        $this->docker->displayWebLink();
    }

    private function handleSandbox(): int
    {
        return new SandboxCommand($this->docker)->execute();
    }
}
