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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractInvocationCommand;
use Ymir\Cli\Console\OutputStyle;

class PhpVersionCommand extends AbstractInvocationCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'php:version';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information about PHP version on the cloud provider')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = (string) $this->getStringOption($input, 'environment');

        $output->info(sprintf('Get information about the PHP version from the "<comment>%s</comment>" environment', $environment));

        $result = $this->invokeEnvironmentFunction($environment, [
            'php' => '--version',
        ]);

        $output->newLine();
        $output->write("${result['output']}");

        return $result['exitCode'];
    }
}
