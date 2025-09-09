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

namespace Ymir\Cli\Command\WordPress;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Resource\Model\Environment;

class WpCliCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    use HandlesWpCliInvocationTrait;
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'wp';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Execute a WP-CLI command')
            ->addArgument('wp-command', InputArgument::IS_ARRAY, 'The WP-CLI command to execute')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Execute WP-CLI command asynchronously')
            ->addHiddenOption('yolo', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (!$this->getProjectConfiguration()->getProjectType() instanceof AbstractWordPressProjectType) {
            throw new UnsupportedProjectException('You can only use this command with WordPress, Bedrock or Radicle projects');
        }

        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to execute the WP-CLI command on?');

        $async = $this->input->getBooleanOption('async') || $this->input->getBooleanOption('yolo');
        $command = implode(' ', $this->input->getArrayArgument('wp-command'));
        $exitCode = Command::SUCCESS;

        if (empty($command)) {
            $command = $this->output->ask('What WP-CLI command would you like to run?');
        }

        if (str_starts_with($command, 'wp ')) {
            $command = substr($command, 3);
        }

        if (in_array($command, ['shell'])) {
            throw new InvalidInputException(sprintf('The "wp %s" command isn\'t available remotely', $command));
        } elseif (in_array($command, ['db import', 'db export'])) {
            throw new InvalidInputException(sprintf('Please use the "ymir database:%s" command instead of the "wp %s" command', substr($command, 3), $command));
        }

        $this->output->info(sprintf('Running "<comment>wp %s</comment>" %s "<comment>%s</comment>" environment', $command, $async ? 'asynchronously on' : 'on', $environment->getName()));

        $result = $this->invokeWpCliCommand($this->getProject(), $command, $environment, $async ? 0 : null);

        if (!$async) {
            $this->output->newLine();
            $this->output->write("{$result['output']}");

            $exitCode = $result['exitCode'];
        }

        return $exitCode;
    }
}
