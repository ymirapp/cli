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

use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ProjectConfiguration\CacheConfigurationChange;

class CreateCacheCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new cache cluster')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the cache cluster')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the cache cluster will be created')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The cache cluster type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $name = $input->getStringArgument('name');

        if (empty($name)) {
            $name = $output->askSlug('What is the name of the cache cluster');
        }

        $network = $this->apiClient->getNetwork($this->determineOrCreateNetwork('On what network should the cache cluster be created?', $input, $output));

        if (!$network->get('has_nat_gateway') && !$output->confirm('A cache cluster will require Ymir to add a NAT gateway to your network (~$32/month). Would you like to proceed?')) {
            return;
        }

        $type = $this->determineType($network, $input, $output);

        $this->apiClient->createCache((int) $network->get('id'), $name, $type);

        $output->infoWithDelayWarning('Cache cluster created');

        if ($this->projectConfiguration->exists() && $output->confirm('Would you like to add the cache cluster to your project configuration?')) {
            $this->projectConfiguration->applyChangesToEnvironments(new CacheConfigurationChange($name));
        }
    }

    /**
     * Determine the cache cluster type to create.
     */
    private function determineType(Collection $network, Input $input, Output $output): string
    {
        if (!isset($network['provider']['id'])) {
            throw new RuntimeException('The Ymir API failed to return information on the cloud provider');
        }

        $type = $input->getStringOption('type');
        $types = $this->apiClient->getCacheTypes((int) $network['provider']['id']);

        if (null !== $type && !$types->has($type)) {
            throw new InvalidArgumentException(sprintf('The type "%s" isn\'t a valid cache cluster type', $type));
        } elseif (null === $type) {
            $type = (string) $output->choice('What should the cache cluster type be?', $types);
        }

        return $type;
    }
}
