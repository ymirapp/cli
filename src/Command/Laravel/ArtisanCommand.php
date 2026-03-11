<?php

declare(strict_types=1);

/*
 * This file is part of Ymir command-line tool.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Cli\Command\Laravel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\HandlesInvocationTrait;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Command\ParsesConsoleCommandTrait;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\LaravelProjectType;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class ArtisanCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    use HandlesInvocationTrait;
    use ParsesConsoleCommandTrait;

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'artisan';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Execute a Laravel artisan command')
            ->addArgument('artisan-command', InputArgument::IS_ARRAY, 'The artisan command to execute')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Execute the command asynchronously')
            ->addHiddenOption('yolo', null, InputOption::VALUE_NONE);
    }

    /**
     * Invokes the environment console function with the given Artisan command and returns the output.
     */
    protected function invokeArtisanCommand(Project $project, string $command, Environment $environment, ?int $timeout = null): array
    {
        $command = $this->stripCommandPrefix($command, ['php artisan', 'artisan']);

        return $this->invokeEnvironmentFunction($project, $environment, [
            'php' => sprintf('artisan %s', $command),
        ], $timeout);
    }

    /**
     * {@inheritDoc}
     */
    protected function perform()
    {
        if (!$this->getProjectConfiguration()->getProjectType() instanceof LaravelProjectType) {
            throw new UnsupportedProjectException('You can only use this command with Laravel projects');
        }

        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to execute the Artisan command on?');

        $async = $this->input->getBooleanOption('async') || $this->input->getBooleanOption('yolo');
        $command = implode(' ', $this->input->getArrayArgument('artisan-command'));
        $exitCode = Command::SUCCESS;

        if (empty($command)) {
            $command = $this->output->ask('What Artisan command would you like to run?');
        }

        $command = $this->stripCommandPrefix($command, ['php artisan', 'artisan']);

        $commandParts = $this->parseCommand($command);
        $commandName = $commandParts[0] ?? '';

        if ('tinker' === $commandName) {
            throw new InvalidInputException(sprintf('The "artisan %s" command isn\'t available remotely', $command));
        }

        $this->output->info(sprintf('Running "<comment>php artisan %s</comment>" %s "<comment>%s</comment>" environment', $command, $async ? 'asynchronously on' : 'on', $environment->getName()));

        $result = $this->invokeArtisanCommand($this->getProject(), $command, $environment, $async ? 0 : null);

        if (!$async) {
            $this->output->newLine();
            $this->output->write("{$result['output']}");

            $exitCode = $result['exitCode'];
        }

        return $exitCode;
    }
}
