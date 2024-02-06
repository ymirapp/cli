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

namespace Ymir\Cli\Command\Cache;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;

class ModifyCacheCommand extends AbstractCacheCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:modify';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Modify a cache cluster')
            ->addArgument('cache', InputArgument::OPTIONAL, 'The ID or name of the cache cluster to modify')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The cache cluster type');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $cache = $this->determineCache('Which cache cluster would you like to modify', $input, $output);
        $type = $input->getStringOption('type', true);
        $types = $this->apiClient->getCacheTypes($cache['provider']['id']);

        if (null === $type) {
            $type = $output->choice(sprintf('What should the cache cluster type be changed to? <fg=default>(Currently: <comment>%s</comment>)</>', $cache['type']), $types);
        } elseif (!$types->has($type)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid cache cluster type', $type));
        }

        if (!$output->confirm('Modifying the cache cluster will cause your cache cluster to become unavailable for a few minutes. Do you want to proceed?', false)) {
            exit;
        }

        $this->apiClient->updateCache((int) $cache['id'], $type);

        $output->infoWithDelayWarning('Cache cluster modified');
    }
}
