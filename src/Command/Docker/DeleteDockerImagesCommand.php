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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Resource\Model\Project;

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
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, DockerExecutable $dockerExecutable)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->dockerExecutable = $dockerExecutable;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a project\'s local deployment docker images')
            ->addArgument('project', InputArgument::OPTIONAL, 'The ID or name of the project to deployment docker images from')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Delete deployment docker images for all projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $deletePrompt = 'Are you sure you want to delete <comment>all</comment> local Ymir deployment docker images?';
        $pattern = self::ALL_PATTERN;
        $project = null;
        $successMessage = 'All local Ymir deployment docker images deleted successfully';

        if (!$this->input->getBooleanOption('all')) {
            $project = $this->resolve(Project::class, 'Which project would you like to delete local Ymir deployment docker images from?');
        }

        if ($project instanceof Project && !$project->getRepositoryUri()) {
            throw new ResourceStateException(sprintf('The "%s" project hasn\'t been deployed using container images', $project->getName()));
        } elseif ($project instanceof Project) {
            $deletePrompt = sprintf('Are you sure you want to delete the local Ymir deployment docker images for the "<comment>%s</comment>" project?', $project->getName());
            $pattern = $project->getRepositoryUri();
            $successMessage = sprintf('Local Ymir deployment docker images for the "<comment>%s</comment>" project deleted successfully', $project->getName());
        }

        if (!$this->output->confirm($deletePrompt, false)) {
            return;
        }

        $this->dockerExecutable->removeImagesMatchingPattern($pattern);

        $this->output->info($successMessage);
    }
}
