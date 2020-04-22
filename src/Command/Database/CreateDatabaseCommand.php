<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Database;

use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class CreateDatabaseCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the database')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Create a development database (overrides all other options)')
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID of the network on which the database will be created')
            ->addOption('public', null, InputOption::VALUE_NONE, 'The created database should be publicly accessible')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database type to create on the cloud provider')
            ->setDescription('Create a new database');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $name = $output->askSlug('What is the name of the database');
        }

        $network = $this->determineNetwork($input, $output);
        $type = $this->determineType($network, $input, $output);
        $storage = $this->determineStorage($input, $output);
        $public = $this->determinePublic($input, $output);

        $database = $this->apiClient->createDatabase($name, (int) $network['id'], $type, $storage, $public);

        $output->horizontalTable(
            ['Database', new TableSeparator(), 'Username', 'Password', new TableSeparator(), 'Type', 'Public', 'Storage (in GB)'],
            [[$database['name'], new TableSeparator(), $database['username'], $database['password'], new TableSeparator(), $database['type'], $database['publicly_accessible'] ? 'Yes' : 'No', $database['storage']]]
        );

        $output->writeln(sprintf('Creation of the database has begun on the "<info>%s</info>" network (<comment>This takes several minutes</comment>)', $network['name']));
    }

    /**
     * Determine the network to create the database on.
     */
    private function determineNetwork(InputInterface $input, OutputStyle $output): array
    {
        $networks = $this->apiClient->getNetworks($this->getActiveTeamId())->filter(function (array $network) {
            return 'available' === $network['status'];
        });

        if ($networks->isEmpty()) {
            throw new RuntimeException('You must create and provision a network before you can create a database');
        }

        $networkId = $this->getNumericOption($input, 'network');

        if (null !== $networkId && !$networks->contains('id', $networkId)) {
            throw new RuntimeException('The given network ID doesn\'t belong to the currently active team');
        } elseif (null === $networkId && 1 === count($networks)) {
            $networkId = $networks[0]['id'];
        } elseif (null === $networkId && 1 !== count($networks)) {
            $networkId = $output->choiceCollection('On what network should the database be created?', $networks);
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
            || $output->confirm('Should the database be publicly accessible?');
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

        $storage = $output->ask('What should the maximum amount of storage (in GB) allocated to the database be?');

        if (!is_numeric($storage)) {
            throw new RuntimeException('The maximum allocated storage needs to be a numeric value');
        }

        return (int) $storage;
    }

    /**
     * Determine the type of database to create.
     */
    private function determineType(array $network, InputInterface $input, OutputStyle $output): string
    {
        if (!isset($network['provider']['id'])) {
            throw new RuntimeException('The placeholder API failed to return information on the cloud provider');
        }

        $types = $this->apiClient->getDatabaseTypes((int) $network['provider']['id']);

        if ($types->isEmpty()) {
            throw new RuntimeException('The placeholder API failed to return information on the database instance types');
        } elseif ($this->getBooleanOption($input, 'dev')) {
            return $types->keys()->first();
        }

        $type = $this->getStringOption($input, 'type');

        if (null !== $type && !$types->has($type)) {
            throw new RuntimeException('The given type is not a valid database type');
        } elseif (null === $type) {
            $type = (string) $output->choice('What type of database should it be?', $types->all());
        }

        return $type;
    }
}
