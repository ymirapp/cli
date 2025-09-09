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
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseServerTunnelCommand extends AbstractDatabaseTunnelCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:tunnel';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a SSH tunnel to a database server')
            ->addArgument('server', InputArgument::OPTIONAL, 'The ID or name of the database server to create a SSH tunnel to')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'The local port to use to connect to the database server', '3305');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $databaseServer = $this->resolve(DatabaseServer::class, 'Which database server would you like to connect to?');
        $localPort = (int) $this->input->getNumericOption('port');

        if (empty($localPort)) {
            throw new InvalidInputException('You must provide a valid "port" option');
        }

        $tunnel = $this->openSshTunnel($databaseServer, $localPort);

        $this->output->newLine();
        $this->output->info(sprintf('SSH tunnel to the "<comment>%s</comment>" database server opened', $databaseServer->getName()));
        $this->output->writeln(sprintf('<info>Local endpoint:</info> 127.0.0.1:%s', $localPort));
        $this->output->newLine();
        $this->output->writeln('The tunnel will remain open as long as this command is running. Press <comment>Ctrl+C</comment> to close the tunnel.');

        $tunnel->wait();
    }
}
