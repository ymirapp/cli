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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class UploadEnvironmentVariablesCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:upload';

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
            ->setDescription('Upload the environment variables in an environment file to an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to upload environment variables to', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $environment = $input->getStringArgument('environment');
        $fileName = sprintf('.env.%s', $environment);
        $filePath = $this->projectDirectory.'/'.$fileName;

        if (!$this->filesystem->exists($filePath)) {
            throw new RuntimeException(sprintf('No environment file found for the "%s" environment. Please download it using the "%s" command.', $environment, DownloadEnvironmentVariablesCommand::NAME));
        } elseif (!$output->confirm('Uploading the environment file will overwrite all environment variables. Are you sure you want to proceed?', false)) {
            return;
        }

        $this->apiClient->changeEnvironmentVariables($this->projectConfiguration->getProjectId(), $environment, collect(explode(PHP_EOL, (string) file_get_contents($filePath)))->mapWithKeys(function (string $line) {
            $matches = [];
            preg_match('/([^=]*)=(.*)/', $line, $matches);

            return isset($matches[1], $matches[2]) ? [$matches[1] => $matches[2]] : [];
        })->all(), true);

        $output->infoWithRedeployWarning('Environment variables uploaded', $environment);

        if ($output->confirm(sprintf('Do you want to delete the "<comment>%s</comment>" environment file?', $fileName))) {
            $this->filesystem->remove($filePath);
        }
    }
}
