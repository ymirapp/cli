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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class DownloadEnvironmentVariablesCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:download';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, ProjectConfiguration $projectConfiguration, string $projectDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Download an environment\'s environment variables into an environment file')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to download environment variables from', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->input->getStringArgument('environment');
        $filename = sprintf('.env.%s', $environment);

        $this->filesystem->dumpFile($this->projectDirectory.'/'.$filename, $this->apiClient->getEnvironmentVariables($this->projectConfiguration->getProjectId(), $environment)->sortKeys()->map(function (string $value, string $key) {
            return sprintf('%s=%s', $key, $value);
        })->join("\n"));

        $this->output->infoWithValue('Environment variables downloaded to', $filename);
    }
}
