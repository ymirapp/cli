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
use Ymir\Cli\Command\Network\CreateNetworkCommand;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\Exception\CommandCancelledException;

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
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the database will be created')
            ->addOption('public', null, InputOption::VALUE_NONE, 'Whether the created database server should be publicly accessible')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database server')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database server type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $name = $this->getStringArgument($input, 'name');

        if (empty($name) && $input->isInteractive()) {
            $name = $output->askSlug('What is the name of the database server');
        }

        $network = $this->determineNetwork($input, $output);
        $type = $this->determineType($network, $input, $output);
        $storage = $this->determineStorage($input, $output);
        $public = $this->determinePublic($input, $output);

        $database = $this->apiClient->createDatabaseServer($name, (int) $network['id'], $type, $storage, $public);

        $output->horizontalTable(
            ['Database Sever', new TableSeparator(), 'Username', 'Password', new TableSeparator(), 'Type', 'Public', 'Storage (in GB)'],
            [[$database['name'], new TableSeparator(), $database['username'], $database['password'], new TableSeparator(), $database['type'], $database['publicly_accessible'] ? 'yes' : 'no', $database['storage']]]
        );

        $output->infoWithDelayWarning('Database server created');
    }

    /**
     * Determine the network to create the database on.
     */
    private function determineNetwork(InputInterface $input, ConsoleOutput $output): array
    {
        $networkIdOrName = $this->getStringOption($input, 'network', true);
        $networks = $this->apiClient->getTeamNetworks($this->cliConfiguration->getActiveTeamId())->whereNotIn('status', ['deleting', 'failed']);

        if ($networks->isEmpty() && !$output->confirm('Your team doesn\'t have any provisioned networks to create the database server on. Would you like to create one first? <fg=default>(Answering "<comment>no</comment>" will cancel the command.)</>')) {
            throw new CommandCancelledException();
        }

        if ($networks->isEmpty()) {
            $this->retryApi(function () use ($output) {
                $this->invoke($output, CreateNetworkCommand::NAME);
            }, 'Do you want to try creating a network again?', $output);

            return $this->apiClient->getTeamNetworks($this->cliConfiguration->getActiveTeamId())->last();
        } elseif (!$networks->isEmpty() && empty($networkIdOrName)) {
            $networkIdOrName = $output->choiceWithResourceDetails('On what network should the database server be created?', $networks);
        }

        $network = $networks->firstWhere('id', $networkIdOrName) ?? $networks->firstWhere('name', $networkIdOrName);

        if (!is_array($network)) {
            throw new RuntimeException(sprintf('Unable to find a network with "%s" as the ID or name', $networkIdOrName));
        }

        return $network;
    }

    /**
     * Determine whether the database should be publicly accessible or not.
     */
    private function determinePublic(InputInterface $input, ConsoleOutput $output): bool
    {
        return $this->getBooleanOption($input, 'dev')
            || $this->getBooleanOption($input, 'public')
            || $output->confirm('Should the database server be publicly accessible?');
    }

    /**
     * Determine the maximum amount of storage allocated to the database.
     */
    private function determineStorage(InputInterface $input, ConsoleOutput $output): int
    {
        if ($this->getBooleanOption($input, 'dev')) {
            return 25;
        }

        $storage = $this->getNumericOption($input, 'storage');

        while (!is_numeric($storage)) {
            $storage = $output->ask('What should the maximum amount of storage (in GB) allocated to the database server be?', '50');

            if (!is_numeric($storage)) {
                $output->newLine();
                $output->error('The maximum allocated storage needs to be a numeric value');
            }
        }

        return (int) $storage;
    }

    /**
     * Determine the database server type to create.
     */
    private function determineType(array $network, InputInterface $input, ConsoleOutput $output): string
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
