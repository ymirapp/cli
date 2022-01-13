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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class CreateEnvironmentCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new environment')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment to create')
            ->addOption('use-image', null, InputOption::VALUE_NONE, 'Whether the environment will be deployed using a container image');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getStringArgument($input, 'name') ?: $output->ask('What is the name of the environment');

        $this->apiClient->createEnvironment($this->projectConfiguration->getProjectId(), $name);

        $this->projectConfiguration->addEnvironment($name, $this->getBooleanOption($input, 'use-image') ? ['deployment' => 'image'] : null);

        $output->info('Environment created');
    }
}
