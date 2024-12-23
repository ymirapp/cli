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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class DeleteDockerImagesCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:delete-images';

    /**
     * The grep pattern used with "--all" option.
     *
     * @var string
     */
    private const ALL_PATTERN = 'dkr.ecr';

    /**
     * The Docker executable.
     *
     * @var DockerExecutable
     */
    private $dockerExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, DockerExecutable $dockerExecutable, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->dockerExecutable = $dockerExecutable;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a project\'s deployment docker images')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete deployment docker images for all projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $pattern = $this->input->getBooleanOption('all') ? self::ALL_PATTERN : null;

        if (!is_string($pattern)) {
            $pattern = $this->determinePattern();
        }

        if (!is_string($pattern) || !$this->output->confirm(self::ALL_PATTERN === $pattern ? 'Are you sure you want to delete deployment docker images for all projects?' : 'Are you sure you want to delete the project\'s deployment docker images?', false)) {
            return;
        }

        $this->dockerExecutable->removeImagesMatchingPattern($pattern);

        $this->output->info('Deployment docker images deleted successfully');
    }

    /**
     * Determine the grep pattern to use.
     */
    private function determinePattern(): ?string
    {
        $project = $this->projectConfiguration->exists() ? $this->apiClient->getProject($this->projectConfiguration->getProjectId()) : null;

        if (null !== $project && !empty($project['repository_uri'])) {
            return $project['repository_uri'];
        } elseif (null !== $project && empty($project['repository_uri'])) {
            throw new RuntimeException(sprintf('The "%s" project has\'t been deployed using container images', $project['name']));
        } elseif (!$this->input->isInteractive()) {
            throw new RuntimeException('Must run command inside an existing project or with "--all" option in non-interactive mode');
        }

        $this->output->warning('No project detected in the current directory');

        return $this->output->confirm('Do you want to delete all deployment docker images?', false) ? self::ALL_PATTERN : null;
    }
}
