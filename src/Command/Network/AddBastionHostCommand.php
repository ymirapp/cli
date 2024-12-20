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

namespace Ymir\Cli\Command\Network;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class AddBastionHostCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:bastion:add';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The path to the user's home directory.
     *
     * @var string
     */
    private $homeDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, string $homeDirectory, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->homeDirectory = rtrim($homeDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Add a bastion host to the network')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to add a bastion host to');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $network = $this->apiClient->getNetwork($this->determineNetwork('Which network would like to add a bastion host to'));

        $bastionHost = $this->apiClient->addBastionHost((int) $network->get('id'));

        $this->output->infoWithDelayWarning('Bastion host added');
        $this->output->newLine();
        $this->output->comment('SSH private key:');
        $this->output->newLine();
        $this->output->writeln($bastionHost['private_key']);

        if (!is_dir($this->homeDirectory.'/.ssh') || !$this->output->confirm('Would you like to create the SSH private key in your ~/.ssh directory?')) {
            return;
        }

        $privateKeyFilename = $this->homeDirectory.'/.ssh/ymir-'.$network->get('name');

        if ($this->filesystem->exists($privateKeyFilename) && !$this->output->confirm('A SSH key already exists for this network. Do you want to overwrite it?', false)) {
            return;
        }

        $this->filesystem->dumpFile($privateKeyFilename, $bastionHost->get('private_key'));
        $this->filesystem->chmod($privateKeyFilename, 0600);

        $this->output->infoWithValue('SSH private key created', $privateKeyFilename);

        $sshConfigFilename = $this->homeDirectory.'/.ssh/config';

        if (!$this->filesystem->exists($sshConfigFilename) || !$this->output->confirm('Would you like to configure SSH to connect to the bastion host?')) {
            return;
        }

        $this->output->infoWithWarning('Waiting for bastion host to get assigned a public domain name', 'takes a few minutes');

        $bastionHost = $this->wait(function () use ($bastionHost) {
            $bastionHost = $this->apiClient->getBastionHost((int) $bastionHost->get('id'));

            return 'available' === $bastionHost->get('status') ? $bastionHost : [];
        }, 300, 5);

        $this->filesystem->appendToFile($sshConfigFilename, sprintf("\nHost %s\n  User ec2-user\n  IdentitiesOnly yes\n  IdentityFile %s\n", $bastionHost->get('endpoint'), $privateKeyFilename));

        $this->output->newLine();
        $this->output->infoWithValue('SSH configured. Login using', sprintf('ssh %s', $bastionHost->get('endpoint')));
    }
}
