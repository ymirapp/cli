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

class EnvironmentUpCommand extends AbstractEnvironmentMaintenanceModeCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:up';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Take an environment out of maintenance mode')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to take out of maintenance mode');
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfirmationQuestion(Environment $environment): string
    {
        return sprintf('This will redeploy the "<comment>%s</comment>" environment of the "<comment>%s</comment>" project and rerun deployment commands. Are you sure you want to take it out of maintenance mode?', $environment->getName(), $this->getProject()->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentQuestion(): string
    {
        return 'Which <comment>%s</comment> environment would you like to take out of maintenance mode?';
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaintenanceMode(): string
    {
        return 'false';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(): string
    {
        return 'Environment redeployed after requesting maintenance mode removal';
    }
}
