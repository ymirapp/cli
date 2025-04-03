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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Project\Configuration\CacheConfigurationChange;

class CreateCacheCommand extends AbstractCacheCommand
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
            ->addOption('engine', null, InputOption::VALUE_REQUIRED, 'The engine used by the cache cluster', 'valkey')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the cache cluster will be created')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The cache cluster type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $engine = strtolower($this->input->getStringOption('engine'));
        $name = $this->input->getStringArgument('name');

        if (!in_array($engine, ['redis', 'valkey'])) {
            throw new InvalidInputException('The engine must be either "redis" or "valkey"');
        }

        if (empty($name)) {
            $name = $this->output->askSlug('What is the name of the cache cluster');
        }

        if (empty($name)) {
            throw new InvalidInputException('Cache cluster name is required');
        }

        $network = $this->apiClient->getNetwork($this->determineOrCreateNetwork('On what network should the cache cluster be created?'));

        if (!$network->get('has_nat_gateway') && !$this->output->confirm('A cache cluster will require Ymir to add a NAT gateway to your network (~$32/month). Would you like to proceed?')) {
            throw new CommandCancelledException();
        }

        $type = $this->determineType($network, $engine);

        $this->apiClient->createCache((int) $network->get('id'), $name, $engine, $type);

        $this->output->infoWithDelayWarning('Cache cluster created');

        if ($this->projectConfiguration->exists() && $this->output->confirm('Would you like to add the cache cluster to your project configuration?')) {
            $this->projectConfiguration->applyChangesToEnvironments(new CacheConfigurationChange($name));
        }
    }

    /**
     * Determine the cache cluster type to create.
     */
    private function determineType(Collection $network, string $engine): string
    {
        if (!isset($network['provider']['id'])) {
            throw new RuntimeException('The Ymir API failed to return information on the cloud provider');
        }

        $type = $this->input->getStringOption('type');
        $types = $this->getCacheTypeDescriptions((int) $network['provider']['id'], $engine);

        if (null !== $type && !$types->has($type)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid cache cluster type', $type));
        } elseif (null === $type) {
            $type = (string) $this->output->choice('What should the cache cluster type be?', $types);
        }

        return $type;
    }
}
