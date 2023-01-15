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

use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputInterface;

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
            ->addOption('network', null, InputOption::VALUE_REQUIRED, 'The ID or name of the network on which the database will be created')
            ->addOption('private', null, InputOption::VALUE_NONE, 'The created database server won\'t be publicly accessible')
            ->addOption('public', null, InputOption::VALUE_NONE, 'The created database server will be publicly accessible')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database server')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database server type to create on the cloud provider');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getStringArgument($input, 'name');

        if (empty($name)) {
            $name = $output->askSlug('What is the name of the database server');
        }

        $network = $this->apiClient->getNetwork($this->determineOrCreateNetwork('On what network should the database server be created?', $input, $output));
        $type = $this->determineType($network, $input, $output);
        $storage = $this->determineStorage($input, $output);
        $public = $this->determinePublic($input, $output);

        if (!$public && !$network->get('has_nat_gateway') && !$output->confirm('A private database server will require Ymir to add a NAT gateway to your network (~$32/month). Would you like to proceed? (Answering "no" will make the database server publicly accessible.)')) {
            $public = true;
        }

        $database = $this->apiClient->createDatabaseServer($name, (int) $network['id'], $type, $storage, $public);

        $output->important(sprintf('Please write down the password shown below as it won\'t be displayed again. Ymir will inject it automatically whenever you assign this database server to a project. If you lose the password, use the "<comment>%s</comment>" command to generate a new one.', RotateDatabaseServerPasswordCommand::NAME));
        $output->newLine();

        $output->horizontalTable(
            ['Database Sever', new TableSeparator(), 'Username', 'Password', new TableSeparator(), 'Type', 'Public', 'Storage (in GB)'],
            [[$database['name'], new TableSeparator(), $database['username'], $database['password'], new TableSeparator(), $database['type'], $output->formatBoolean($database['publicly_accessible']), $database['storage']]]
        );

        $output->infoWithDelayWarning('Database server created');
    }

    /**
     * Determine whether the database should be publicly accessible or not.
     */
    private function determinePublic(InputInterface $input, OutputInterface $output): bool
    {
        if ($this->getBooleanOption($input, 'public')) {
            return true;
        } elseif ($this->getBooleanOption($input, 'private')) {
            return false;
        }

        return $output->confirm('Should the database server be publicly accessible?');
    }

    /**
     * Determine the maximum amount of storage allocated to the database.
     */
    private function determineStorage(InputInterface $input, OutputInterface $output): int
    {
        $storage = $this->getNumericOption($input, 'storage');

        while (!is_numeric($storage)) {
            $storage = $output->ask('What should the maximum amount of storage (in GB) allocated to the database server be?', '50', function ($storage) {
                if (!is_numeric($storage)) {
                    throw new \Exception('The maximum allocated storage needs to be a numeric value');
                }

                return $storage;
            });
        }

        return (int) $storage;
    }

    /**
     * Determine the database server type to create.
     */
    private function determineType(Collection $network, InputInterface $input, OutputInterface $output): string
    {
        if (!isset($network['provider']['id'])) {
            throw new RuntimeException('The Ymir API failed to return information on the cloud provider');
        }

        $type = $this->getStringOption($input, 'type');
        $types = $this->apiClient->getDatabaseServerTypes((int) $network['provider']['id']);

        if (null !== $type && !$types->has($type)) {
            throw new InvalidArgumentException(sprintf('The type "%s" isn\'t a valid database type', $type));
        } elseif (null === $type) {
            $type = (string) $output->choice('What should the database server type be?', $types);
        }

        return $type;
    }
}
