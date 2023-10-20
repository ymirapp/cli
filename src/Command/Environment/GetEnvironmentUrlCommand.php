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
use Symfony\Component\Process\Process;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class GetEnvironmentUrlCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:url';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get the environment URL and copy it to the clipboard')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the URL of', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $this->displayEnvironmentUrlAndCopyToClipboard($output, $this->apiClient->getEnvironmentVanityDomainName($this->projectConfiguration->getProjectId(), $input->getStringArgument('environment')));
    }

    /**
     * Generate the environment URL, copy it to the clipboard and then displays it in the console.
     */
    private function displayEnvironmentUrlAndCopyToClipboard(Output $output, string $domainName)
    {
        $clipboardCommand = 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ? 'clip' : 'pbcopy';
        $url = 'https://'.$domainName;

        Process::fromShellCommandline(sprintf('echo %s | %s', $url, $clipboardCommand))->run();

        $output->infoWithValue('Environment URL is', $url, 'copied to clipboard');
    }
}
