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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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
        if (!$this->getBooleanOption($input, 'no-interaction')
            && !$output->confirm('Are you sure you want to delete this database?', false)
        ) {
            return;
        }

        $idOrName = $input->getArgument('database');

        if (null === $idOrName || is_array($idOrName)) {
            throw new RuntimeException('The "database" argument must be a string value');
        }

        $database = $this->getDatabase($idOrName);

        if (isset($database['status']) && 'deleting' === $database['status']) {
            throw new RuntimeException(sprintf('The database with the ID or name "%s" is already being deleted', $idOrName));
        }

        $this->apiClient->deleteDatabase((int) $database['id']);

        $output->writeln(sprintf('Deletion of the database with the ID or name "<info>%s</info>" has begun (This takes several several minutes)', $idOrName));
    }

    /**
     * Get the database information from the given database ID or name.
     */
    private function getDatabase(string $idOrName): array
    {
        $database = null;
        $databases = $this->apiClient->getDatabases($this->getActiveTeamId());

        if (is_numeric($idOrName)) {
            $database = $databases->firstWhere('id', $idOrName);
        } elseif (is_string($idOrName)) {
            $database = $databases->firstWhere('name', $idOrName);
        }

        if (!is_array($database) || !isset($database['id']) || !is_numeric($database['id'])) {
            throw new RuntimeException(sprintf('Unable to find a database with "%s" as the ID or name', $idOrName));
        }

        return $database;
    }
}
