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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function perform(Input $input, Output $output)
    {
        $databaseServer = $this->determineDatabaseServer('Which database server would you like to modify?', $input, $output);

        if (self::AURORA_DATABASE_TYPE === $databaseServer['type']) {
            throw new RuntimeException('You cannot modify an Aurora database server');
        }

        $this->apiClient->updateDatabaseServer((int) $databaseServer['id'], $this->determineStorage($input, $output, (int) $databaseServer['storage']), $this->determineType($input, $output, $databaseServer['network']['provider']['id'], $databaseServer['type']));

        $output->infoWithDelayWarning('Database server modified');
    }

    /**
     * Determine the new maximum amount of storage allocated to the database.
     */
    private function determineStorage(Input $input, Output $output, int $storage): int
    {
        $storageOption = $input->getNumericOption('storage');

        if (null !== $storageOption) {
            return $storageOption;
        } elseif (!$input->isInteractive()) {
            return $storage;
        }

        $storage = $output->ask(sprintf('What should the new maximum amount of storage (in GB) allocated to the database server be? <fg=default>(Currently: <comment>%sGB</comment>)</>', $storage), (string) $storage);

        if (!is_numeric($storage)) {
            throw new InvalidArgumentException('The maximum allocated storage needs to be a numeric value');
        }

        return (int) $storage;
    }

    /**
     * Determine the new database server type.
     */
    private function determineType(Input $input, Output $output, int $providerId, string $type): string
    {
        $typeOption = $input->getStringOption('type');

        if (null === $typeOption && !$input->isInteractive()) {
            return $type;
        }

        $types = $this->apiClient->getDatabaseServerTypes($providerId);

        if (null !== $typeOption && $types->has($typeOption)) {
            return $type;
        } elseif (null !== $typeOption && !$types->has($typeOption)) {
            throw new InvalidArgumentException(sprintf('The type "%s" isn\'t a valid database type', $typeOption));
        }

        $newType = $output->choice(sprintf('What should the database server type be changed to? <fg=default>(Currently: <comment>%s</comment>)</>', $type), $types, $type);

        if ($newType !== $type && !$output->confirm('Modifying the database server type will cause your database to become unavailable for a few minutes. Do you want to proceed?', false)) {
            exit;
        }

        return $newType;
    }
}
