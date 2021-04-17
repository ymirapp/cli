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
use Ymir\Cli\Console\ConsoleOutput;

class InvalidateEnvironmentCacheCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:invalidate-cache';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Invalidate the environment\'s content delivery network cache')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to invalidate the cache of', 'staging')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to invalidate on the content delivery network', ['*']);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $this->apiClient->invalidateCache($this->projectConfiguration->getProjectId(), $this->getStringArgument($input, 'environment'), $this->getArrayOption($input, 'path'));
    }
}
