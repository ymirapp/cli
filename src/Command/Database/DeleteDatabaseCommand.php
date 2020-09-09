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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class DeleteDatabaseCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('database', InputArgument::REQUIRED, 'The ID or name of the database to delete')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question')
            ->setDescription('Delete an existing database');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $databaseIdOrName = $this->getStringArgument($input, 'database');
        $database = $this->apiClient->getDatabase($databaseIdOrName);

        if (isset($database['status']) && 'deleting' === $database['status']) {
            throw new RuntimeException(sprintf('The database with the ID or name "%s" is already being deleted', $databaseIdOrName));
        }

        if (!$output->confirm('Are you sure you want to delete this database?', false)) {
            return;
        }

        $this->apiClient->deleteDatabase((int) $database['id']);

        $output->infoWithDelayWarning('Database deleted');
    }
}
