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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Database\Connection;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Executable\SshExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Resource\Model\BastionHost;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;

abstract class AbstractDatabaseTunnelCommand extends AbstractCommand
{
    /**
     * The SSH executable.
     *
     * @var SshExecutable
     */
    private $sshExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, SshExecutable $sshExecutable)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->sshExecutable = $sshExecutable;
    }

    /**
     * Get the connection to the database by prompting for any missing information.
     */
    protected function getConnection(string $databaseQuestion, string $databaseServerQuestion): Connection
    {
        $databaseServer = $this->resolve(DatabaseServer::class, $databaseServerQuestion);
        $database = $this->resolve(Database::class, $databaseQuestion, $databaseServer)->getName();
        $password = $this->input->getStringOption('password', true);
        $user = $this->input->getStringOption('user', true);

        if (empty($user)) {
            $user = $this->output->ask('Which user do you want to use to connect to the database server?', 'ymir');
        }

        if (empty($password)) {
            $password = (string) $this->output->askHidden(sprintf('What is the "<comment>%s</comment>" password?', $user));
        }

        return new Connection($database, $databaseServer, $user, $password);
    }

    /**
     * Open a SSH tunnel to a private database server.
     */
    protected function openSshTunnel(DatabaseServer $databaseServer, int $localPort = 3305): Process
    {
        if ('available' !== $databaseServer->getStatus() || empty($databaseServer->getEndpoint())) {
            throw new InvalidInputException(sprintf('The "%s" database server isn\'t available', $databaseServer->getName()));
        } elseif ($databaseServer->isPublic()) {
            throw new InvalidInputException(sprintf('The "%s" database server is publicly accessible and isn\'t on a private subnet', $databaseServer->getName()));
        } elseif (3306 === $localPort) {
            throw new InvalidInputException('Cannot use port 3306 as the local port for the SSH tunnel to the database server');
        }

        $this->output->info(sprintf('Opening SSH tunnel to the "<comment>%s</comment>" database server...', $databaseServer->getName()));

        $bastionHost = $databaseServer->getNetwork()->getBastionHost();

        if (!$bastionHost instanceof BastionHost) {
            throw new ResourceStateException(sprintf('The "%s" network doesn\'t have a bastion host to connect to, but you can add one to the network with the "%s" command', $databaseServer->getNetwork()->getName(), AddBastionHostCommand::NAME));
        }

        return $this->sshExecutable->openTunnelToBastionHost($bastionHost, $localPort, $databaseServer->getEndpoint(), 3306);
    }
}
