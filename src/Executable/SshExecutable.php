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

namespace Ymir\Cli\Executable;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Executable\SshPortInUseException;
use Ymir\Cli\Process\Process;

class SshExecutable extends AbstractExecutable
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The SSH directory.
     *
     * @var string
     */
    private $sshDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, ?string $sshDirectory = null)
    {
        $this->filesystem = $filesystem;
        $this->sshDirectory = $sshDirectory ?? rtrim((string) getenv('HOME'), '/').'/.ssh';
    }

    public function getDisplayName(): string
    {
        return 'SSH';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        return 'ssh';
    }

    /**
     * Opens an SSH tunnel to a bastion host and returns the running tunnel process.
     */
    public function openTunnelToBastionHost(array $bastionHost, int $localPort, string $remoteHost, int $remotePort, ?string $cwd = null): Process
    {
        if (!isset($bastionHost['endpoint'], $bastionHost['private_key'])) {
            throw new InvalidArgumentException('Bastion host configuration must contain an "endpoint" and a "private_key"');
        }

        if (!is_dir($this->sshDirectory)) {
            $this->filesystem->mkdir($this->sshDirectory, 0700);
        }

        $identityFilePath = $this->sshDirectory.'/ymir-tunnel';

        $this->filesystem->dumpFile($identityFilePath, $bastionHost['private_key']);
        $this->filesystem->chmod($identityFilePath, 0600);

        $process = $this->getProcess(sprintf('ec2-user@%s -i %s -o LogLevel=debug -L %s:%s:%s -N', $bastionHost['endpoint'], $identityFilePath, $localPort, $remoteHost, $remotePort), $cwd, null);
        $process->start(function ($type, $buffer) use ($localPort) {
            if (Process::ERR === $type && false !== stripos($buffer, sprintf('%s: address already in use', $localPort))) {
                throw new SshPortInUseException($localPort);
            }
        });

        return $process;
    }
}
