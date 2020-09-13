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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\ProjectConfiguration;

/**
 * Base command for interacting with a project.
 */
abstract class AbstractProjectCommand extends AbstractCommand
{
    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * Generate the environment URL, copy it to the clipboard and then displays it in the console.
     */
    protected function displayEnvironmentUrlAndCopyToClipboard(OutputStyle $output, string $domainName)
    {
        $clipboardCommand = 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ? 'clip' : 'pbcopy';
        $url = 'https://'.$domainName;

        Process::fromShellCommandline(sprintf('echo %s | %s', $url, $clipboardCommand))->run();

        $output->infoWithValue('Environment URL is', $url, 'copied to clipboard');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (InitializeProjectCommand::NAME !== $this->getName()) {
            $this->projectConfiguration->validate();
        }

        return parent::execute($input, $output);
    }
}
