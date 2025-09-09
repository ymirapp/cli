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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Requirement\DatabaseServerStorageRequirement;
use Ymir\Cli\Resource\Requirement\DatabaseServerTypeRequirement;

class ModifyDatabaseServerCommand extends AbstractCommand
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
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to modify?');

        if (DatabaseServer::AURORA_DATABASE_TYPE === $databaseServer->getType()) {
            throw new ResourceStateException('You cannot modify an Aurora database server');
        }

        $newStorage = $this->fulfill(new DatabaseServerStorageRequirement(sprintf('What should the new maximum amount of storage (in GB) allocated to the database server be? <fg=default>(Currently: <comment>%sGB</comment>)</>', $databaseServer->getStorage()), (string) $databaseServer->getStorage()), ['type' => $databaseServer->getType()]);

        if (empty($newStorage)) {
            throw new InvalidInputException('You must provide a database server storage value');
        } elseif ($newStorage < $databaseServer->getStorage()) {
            throw new InvalidInputException('You cannot reduce the maximum amount of storage allocated to the database server');
        } elseif ($newStorage > $databaseServer->getStorage() && !$this->output->warningConfirmation('Modifying the database server storage is an irreversible change')) {
            return;
        }

        $newType = $this->fulfill(new DatabaseServerTypeRequirement(sprintf('What should the database server type be changed to? <fg=default>(Currently: <comment>%s</comment>)</>', $databaseServer->getType()), $databaseServer->getType()), ['network' => $databaseServer->getNetwork()]);

        if ($newType !== $databaseServer->getType() && !$this->output->warningConfirmation('Modifying the database server type will cause your database to become unavailable for a few minutes')) {
            return;
        }

        $this->apiClient->updateDatabaseServer($databaseServer, $newStorage, $newType);

        $this->output->infoWithDelayWarning('Database server modified');
    }
}
