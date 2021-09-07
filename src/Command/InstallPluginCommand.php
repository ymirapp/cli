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

namespace Ymir\Cli\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\GitHubClient;
use Ymir\Cli\Process\Process;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

class InstallPluginCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'install-plugin';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The API client that interacts with the GitHub API.
     *
     * @var GitHubClient
     */
    private $gitHubClient;

    /**
     * The project directory where we want to install the plugin.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, GitHubClient $gitHubClient, ProjectConfiguration $projectConfiguration, string $projectDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->gitHubClient = $gitHubClient;
        $this->projectDirectory = rtrim($projectDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Installs the Ymir WordPress plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $message = 'Installing Ymir plugin';
        $projectType = strtolower($this->projectConfiguration->getProjectType());

        if (!in_array($projectType, ['bedrock', 'wordpress'])) {
            throw new RuntimeException('Can only install plugin for "bedrock" and "wordpress" projects');
        }

        if ('bedrock' === $projectType) {
            $output->info($message.' using Composer');
            Process::runShellCommandline('composer require ymirapp/wordpress-plugin');
        } elseif ('wordpress' === $projectType) {
            $output->info($message.' from GitHub');
            $this->installFromGitHub();
        }

        $output->info('Ymir plugin installed');
    }

    /**
     * Install the WordPress plugin by downloading it from GitHub.
     */
    private function installFromGitHub()
    {
        $pluginsDirectory = $this->projectDirectory.'/wp-content/plugins';

        $this->gitHubClient->downloadLatestVersion('ymirapp/wordpress-plugin')->extractTo($pluginsDirectory);

        $files = Finder::create()
            ->directories()
            ->in($pluginsDirectory)
            ->path('/^ymirapp-wordpress-plugin-/')
            ->depth('== 0');

        if (1 !== count($files)) {
            throw new RuntimeException('Unable to find the extracted WordPress plugin');
        }

        $this->filesystem->rename($pluginsDirectory.'/'.Arr::first($files)->getFilename(), $pluginsDirectory.'/ymir-wordpress-plugin', true);
    }
}
