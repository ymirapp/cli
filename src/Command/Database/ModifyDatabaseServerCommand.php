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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Exception\InvalidInputException;

class ModifyDatabaseServerCommand extends AbstractDatabaseServerCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:modify';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Modify a database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to modify')
            ->addOption('storage', null, InputOption::VALUE_REQUIRED, 'The maximum amount of storage (in GB) allocated to the database server')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The database server type');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to modify?');

        if (self::AURORA_DATABASE_TYPE === $databaseServer['type']) {
            throw new RuntimeException('You cannot modify an Aurora database server');
        }

        $this->apiClient->updateDatabaseServer((int) $databaseServer['id'], $this->determineStorage((int) $databaseServer['storage']), $this->determineType($databaseServer['network']['provider']['id'], $databaseServer['type']));

        $this->output->infoWithDelayWarning('Database server modified');
    }

    /**
     * Determine the new maximum amount of storage allocated to the database.
     */
    private function determineStorage(int $storage): int
    {
        $storageOption = $this->input->getNumericOption('storage');

        if (null !== $storageOption) {
            return $storageOption;
        } elseif (!$this->input->isInteractive()) {
            return $storage;
        }

        $storage = $this->output->ask(sprintf('What should the new maximum amount of storage (in GB) allocated to the database server be? <fg=default>(Currently: <comment>%sGB</comment>)</>', $storage), (string) $storage);

        if (!is_numeric($storage)) {
            throw new InvalidInputException('The maximum allocated storage needs to be a numeric value');
        }

        return (int) $storage;
    }

    /**
     * Determine the new database server type.
     */
    private function determineType(int $providerId, string $type): string
    {
        $typeOption = $this->input->getStringOption('type');

        if (null === $typeOption && !$this->input->isInteractive()) {
            return $type;
        }

        $types = $this->apiClient->getDatabaseServerTypes($providerId);

        if (null !== $typeOption && $types->has($typeOption)) {
            return $type;
        } elseif (null !== $typeOption && !$types->has($typeOption)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid database type', $typeOption));
        }

        $newType = $this->output->choice(sprintf('What should the database server type be changed to? <fg=default>(Currently: <comment>%s</comment>)</>', $type), $types, $type);

        if ($newType !== $type && !$this->output->confirm('Modifying the database server type will cause your database to become unavailable for a few minutes. Do you want to proceed?', false)) {
            exit;
        }

        return $newType;
    }
}
