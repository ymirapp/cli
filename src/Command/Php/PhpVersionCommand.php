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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\HandlesInvocationTrait;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Resource\Model\Environment;

class PhpVersionCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    use HandlesInvocationTrait;

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
            ->setDescription('Get the PHP version information on the cloud provider')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the PHP version of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to get the PHP version information from?');

        $this->output->info(sprintf('Getting PHP version information from the "<comment>%s</comment>" environment', $environment->getName()));

        $result = $this->invokePhpCommand($this->getProject(), '--version', $environment);

        $this->output->newLine();
        $this->output->write("{$result['output']}");

        return $result['exitCode'];
    }
}
