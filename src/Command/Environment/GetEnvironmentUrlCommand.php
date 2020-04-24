<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Environment;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;

class GetEnvironmentUrlCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:url';

    /**
     * The placeholder project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get the environment URL and copy it to the clipboard')
            ->addArgument('environment', InputArgument::REQUIRED, 'The name of the environment');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = $input->getArgument('environment');

        if (!is_string($environment)) {
            throw new RuntimeException('Invalid "environment" argument given');
        }

        $this->projectConfiguration->validate([$environment]);

        $environment = $this->apiClient->getEnvironment($this->projectConfiguration->getProjectId(), $environment);

        if (!$environment->has('vanity_domain')) {
            throw new RuntimeException('Unable to get the environment domain');
        }

        $clipboardCommand = 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ? 'clip' : 'pbcopy';
        $url = 'https://'.$environment->get('vanity_domain');

        Process::fromShellCommandline(sprintf('echo %s | %s', $url, $clipboardCommand))->run();

        $output->infoWithValue('Environment URL is', $url, 'copied to clipboard');
    }
}
