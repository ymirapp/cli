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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Resource\Model\Environment;

class ChangeEnvironmentVariableCommand extends AbstractCommand implements LocalProjectCommandInterface
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
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment where the environment variable is')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment variable')
            ->addArgument('value', InputArgument::OPTIONAL, 'The value of the environment variable');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to change an environment variable of?');

        $name = $this->input->getStringArgument('name');
        $value = $this->input->getStringArgument('value');

        if (empty($name)) {
            $name = $this->output->ask('What is the name of the environment variable being changed?');
        }

        if (empty($value)) {
            $value = $this->output->ask('Which value should the environment variable have?');
        }

        $this->apiClient->changeEnvironmentVariables($this->getProject(), $environment, [
            $name => $value,
        ]);

        $this->output->infoWithRedeployWarning('Environment variable changed', $environment->getName());
    }
}
