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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class ChangeEnvironmentVariableCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:change';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change an environment variable')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment where the environment variable is', 'staging')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment variable')
            ->addArgument('value', InputArgument::OPTIONAL, 'The value of the environment variable');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $environment = $input->getStringArgument('environment');
        $name = $input->getStringArgument('name');
        $value = $input->getStringArgument('value');

        if (empty($name)) {
            $name = $output->ask('What is the name of the environment variable');
        }

        if (empty($value)) {
            $value = $output->ask('What is the value of the environment variable');
        }

        $this->apiClient->changeEnvironmentVariables($this->projectConfiguration->getProjectId(), $environment, [
            $name => $value,
        ]);

        $output->infoWithRedeployWarning('Environment variable changed', $environment);
    }
}
