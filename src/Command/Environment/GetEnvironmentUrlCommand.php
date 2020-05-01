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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputStyle;

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
