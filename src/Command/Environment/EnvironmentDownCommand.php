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
use Ymir\Cli\Resource\Model\Environment;

class EnvironmentDownCommand extends AbstractEnvironmentMaintenanceModeCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:down';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Put an environment in maintenance mode')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to put in maintenance mode');
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfirmationQuestion(Environment $environment): string
    {
        return sprintf('This will redeploy the "<comment>%s</comment>" environment of the "<comment>%s</comment>" project and rerun deployment commands. Are you sure you want to put it in maintenance mode?', $environment->getName(), $this->getProject()->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentQuestion(): string
    {
        return 'Which <comment>%s</comment> environment would you like to put in maintenance mode?';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaintenanceMode(): string
    {
        return 'true';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(): string
    {
        return 'Environment redeployed after requesting maintenance mode';
    }
}
