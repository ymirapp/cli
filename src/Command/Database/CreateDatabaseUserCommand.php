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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class CreateDatabaseUserCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:user:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('database', InputArgument::REQUIRED, 'The ID or name of the database to delete')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new database user')
            ->setDescription('Create a new user on a database');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $idOrName = $input->getArgument('database');

        if (null === $idOrName || is_array($idOrName)) {
            throw new RuntimeException('The "database" argument must be a string value');
        }

        $database = $this->apiClient->getDatabase($idOrName);

        if (isset($database['status']) && 'available' !== $database['status']) {
            throw new RuntimeException(sprintf('The database with the ID or name "%s" is unavailable', $idOrName));
        }

        $username = $input->getArgument('username');

        if (null === $username || is_array($username)) {
            throw new RuntimeException('The "username" argument must be a string value');
        }

        $user = $this->apiClient->createDatabaseUser((int) $database['id'], $username);

        $output->horizontalTable(
            ['Username', 'Password'],
            [[$user['username'], $user['password']]]
        );

        $output->info('Database user created');
    }
}
