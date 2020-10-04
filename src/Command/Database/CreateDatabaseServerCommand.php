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

namespace Ymir\Cli\Command\Database;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class CreateDatabaseServerCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new database server')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database server')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Create a development database server (overrides all other options)')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID of the network on which the database will be created')
            ->addOption('public', null, InputOption::VALUE_NONE, 'Whether the created database server should be publicly accessible')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database server')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database server type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $name = $this->getStringArgument($input, 'name');

        if (empty($name) && $input->isInteractive()) {
            $name = $output->askSlug('What is the name of the database server');
        } elseif (empty($name) && !$input->isInteractive()) {
            throw new InvalidArgumentException('You must pass a "name" argument when running in non-interactive mode');
        }

        $network = $this->determineNetwork($input, $output);
        $type = $this->determineType($network, $input, $output);
        $storage = $this->determineStorage($input, $output);
        $public = $this->determinePublic($input, $output);

        $database = $this->apiClient->createDatabaseServer($name, (int) $network['id'], $type, $storage, $public);

        $output->horizontalTable(
            ['Database Sever', new TableSeparator(), 'Username', 'Password', new TableSeparator(), 'Type', 'Public', 'Storage (in GB)'],
            [[$database['name'], new TableSeparator(), $database['username'], $database['password'], new TableSeparator(), $database['type'], $database['publicly_accessible'] ? 'Yes' : 'No', $database['storage']]]
        );

        $output->infoWithDelayWarning('Database server created');
    }

    /**
     * Determine the network to create the database on.
     */
    private function determineNetwork(InputInterface $input, OutputStyle $output): array
    {
        $networks = $this->apiClient->getNetworks($this->cliConfiguration->getActiveTeamId())->filter(function (array $network) {
            return 'available' === $network['status'];
        });

        if ($networks->isEmpty()) {
            throw new RuntimeException('You must create and provision a network before you can create a database server');
        }

        $networkId = $this->getNumericOption($input, 'network');

        if (null !== $networkId && !$networks->contains('id', $networkId)) {
            throw new InvalidArgumentException('The given network ID doesn\'t belong to the currently active team');
        } elseif (null === $networkId && 1 === count($networks)) {
            $networkId = $networks[0]['id'];
        } elseif (null === $networkId && 1 !== count($networks)) {
            $networkId = $output->choiceCollection('On what network should the database server be created?', $networks);
        }

        return $networks->firstWhere('id', $networkId);
    }

    /**
     * Determine whether the database should be publicly accessible or not.
     */
    private function determinePublic(InputInterface $input, OutputStyle $output): bool
    {
        return $this->getBooleanOption($input, 'dev')
            || $this->getBooleanOption($input, 'public')
            || $output->confirm('Should the database server be publicly accessible?');
    }

    /**
     * Determine the maximum amount of storage allocated to the database.
     */
    private function determineStorage(InputInterface $input, OutputStyle $output): int
    {
        if ($this->getBooleanOption($input, 'dev')) {
            return 25;
        }

        $storage = $this->getNumericOption($input, 'storage');

        if (null !== $storage) {
            return $storage;
        }

        $storage = $output->ask('What should the maximum amount of storage (in GB) allocated to the database server be?', '50');

        if (!is_numeric($storage)) {
            throw new InvalidArgumentException('The maximum allocated storage needs to be a numeric value');
        }

        return (int) $storage;
    }

    /**
     * Determine the database server type to create.
     */
    private function determineType(array $network, InputInterface $input, OutputStyle $output): string
    {
        if (!isset($network['provider']['id'])) {
            throw new RuntimeException('The Ymir API failed to return information on the cloud provider');
        }

        $types = $this->apiClient->getDatabaseServerTypes((int) $network['provider']['id']);

        if ($this->getBooleanOption($input, 'dev')) {
            return $types->keys()->first();
        }

        $type = $this->getStringOption($input, 'type');

        if (null !== $type && !$types->has($type)) {
            throw new InvalidArgumentException(sprintf('The type "%s" isn\'t a valid database type', $type));
        } elseif (null === $type) {
            $type = (string) $output->choice('What should the database server type be?', $types->all());
        }

        return $type;
    }
}
