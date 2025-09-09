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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Resource\Model\Environment;

class GetEnvironmentUrlCommand extends AbstractCommand implements LocalProjectCommandInterface
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
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the URL of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like the URL of?');

        $this->displayEnvironmentUrlAndCopyToClipboard($environment->getVanityDomainName());
    }

    /**
     * Generate the environment URL, copy it to the clipboard and then displays it in the console.
     */
    private function displayEnvironmentUrlAndCopyToClipboard(string $domainName): void
    {
        $clipboardCommand = 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ? 'clip' : 'pbcopy';
        $url = 'https://'.$domainName;

        Process::fromShellCommandline(sprintf('echo %s | %s', $url, $clipboardCommand))->run();

        $this->output->infoWithValue('Environment URL is', $url, 'copied to clipboard');
    }
}
