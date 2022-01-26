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

namespace Ymir\Cli\Command\Docker;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Tool\Docker;

class PruneDockerImagesCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:prune';

    /**
     * The grep pattern used with "--all" option.
     *
     * @var string
     */
    private const ALL_PATTERN = 'dkr.ecr';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Prune deployment docker images')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete all deployment docker images');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $pattern = $this->getBooleanOption($input, 'all') ? self::ALL_PATTERN : null;

        if (!is_string($pattern)) {
            $pattern = $this->determinePattern($input, $output);
        }

        if (!is_string($pattern) || !$output->confirm(self::ALL_PATTERN === $pattern ? 'Are you sure you want to delete all deployment docker images?' : 'Are you sure you want to delete the project\'s deployment docker images?', false)) {
            return;
        }

        Docker::rmigrep($pattern);

        $output->info('Deployment docker images pruned successfully');
    }

    /**
     * Determine the grep pattern to use.
     */
    private function determinePattern(InputInterface $input, OutputInterface $output): ?string
    {
        $project = $this->projectConfiguration->exists() ? $this->apiClient->getProject($this->projectConfiguration->getProjectId()) : null;

        if (null !== $project && !empty($project['repository_uri'])) {
            return $project['repository_uri'];
        } elseif (null !== $project && empty($project['repository_uri'])) {
            throw new RuntimeException(sprintf('The "%s" project has\'t been deployed using container images', $project['name']));
        } elseif (!$input->isInteractive()) {
            throw new RuntimeException('Must run command inside an existing project or with "--all" option in non-interactive mode');
        }

        $output->warning('No project detected in the current directory');

        return $output->confirm('Do you want to delete all deployment docker images?', false) ? self::ALL_PATTERN : null;
    }
}
