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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Resource\Model\Environment;

class DeleteEnvironmentCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to delete')
            ->addOption('delete-resources', null, InputOption::VALUE_NONE, 'Whether to delete the environment resources');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" environment?', $environment->getName()), !$this->input->isInteractive())) {
            return;
        }

        $deleteResources = $this->input->getBooleanOption('delete-resources') || $this->output->confirm('Do you want to delete all the environment resources on the cloud provider?', false);

        $this->apiClient->deleteEnvironment($this->getProject(), $environment, $deleteResources);

        if ($this->getProjectConfiguration()->exists() && $this->getProject()->getId() === $this->getProjectConfiguration()->getProjectId()) {
            $this->getProjectConfiguration()->deleteEnvironment($environment->getName());
        }

        $message = 'Environment deleted';
        $deleteResources ? $this->output->infoWithDelayWarning($message) : $this->output->info($message);
    }
}
