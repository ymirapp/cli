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

use Carbon\Carbon;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\Executable\ExecutableException;
use Ymir\Cli\Exception\Executable\SshPortInUseException;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Resource\Model\BastionHost;

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
    public function openTunnelToBastionHost(BastionHost $bastionHost, int $localPort, string $remoteHost, int $remotePort, ?string $cwd = null): Process
    {
        if (!is_dir($this->sshDirectory)) {
            $this->filesystem->mkdir($this->sshDirectory, 0700);
        }

        $identityFilePath = $this->sshDirectory.'/ymir-tunnel';

        $this->filesystem->dumpFile($identityFilePath, $bastionHost->getPrivateKey());
        $this->filesystem->chmod($identityFilePath, 0600);

        $process = $this->getProcess(sprintf('ec2-user@%s -i %s -o LogLevel=debug -L %s:%s:%s -N', $bastionHost->getEndpoint(), $identityFilePath, $localPort, $remoteHost, $remotePort), $cwd, null);
        $process->start(function ($type, $buffer) use ($localPort): void {
            if (Process::ERR === $type && false !== stripos($buffer, sprintf('%s: address already in use', $localPort))) {
                throw new SshPortInUseException($localPort);
            }
        });

        $timeout = Carbon::now()->addSeconds(10);

        while ($process->isRunning() && Carbon::now()->lessThan($timeout)) {
            if (str_contains($process->getIncrementalErrorOutput(), sprintf('Authenticated to %s', $bastionHost->getEndpoint()))) {
                return $process;
            }

            usleep(100000);
        }

        throw new ExecutableException('Attempt to create a SSH tunnel to the bastion host timed out after 10 seconds');
    }
}
