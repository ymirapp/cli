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

namespace Ymir\Cli\Command\Php;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractInvocationCommand;

class PhpInfoCommand extends AbstractInvocationCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'php:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information about PHP on the cloud provider')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get PHP information about.', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->input->getStringArgument('environment');

        $this->output->info(sprintf('Getting information about PHP from the "<comment>%s</comment>" environment', $environment));

        $result = $this->invokePhpCommand('--info', $environment);

        $this->output->newLine();
        $this->output->write("{$result['output']}");

        return $result['exitCode'];
    }
}
