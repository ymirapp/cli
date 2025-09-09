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

namespace Ymir\Cli\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\HiddenInputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\InputDefinition;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Requirement\RequirementInterface;

abstract class AbstractCommand extends Command
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * The console input.
     *
     * @var Input
     */
    protected $input;

    /**
     * The console output.
     *
     * @var Output
     */
    protected $output;

    /**
     * The execution context.
     *
     * @var ExecutionContext|null
     */
    private $context;

    /**
     * The execution context factory.
     *
     * @var ExecutionContextFactory
     */
    private $contextFactory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory)
    {
        $this->apiClient = $apiClient;
        $this->context = null;
        $this->contextFactory = $contextFactory;

        $this->setDefinition(new InputDefinition());

        $this->configure();
    }

    /**
     * Adds an option that doesn't appear in the "help" command.
     */
    public function addHiddenOption($name, $shortcut = null, $mode = null, $default = null)
    {
        $this->getDefinition()->addOption(new HiddenInputOption($name, $shortcut, $mode, $default));

        return $this;
    }

    /**
     * Whether the command must always be run in interactive mode or not.
     */
    public function mustBeInteractive(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->input = new Input($input);
        $this->output = new Output($input, $output);

        return parent::run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->perform() ?? self::SUCCESS;
    }

    /**
     * Fulfill a specific requirement using the current context.
     */
    protected function fulfill(RequirementInterface $requirement, array $fulfilledRequirements = [])
    {
        return $this->getContext()->fulfill($requirement, $fulfilledRequirements);
    }

    /**
     * Get the execution context.
     */
    protected function getContext(): ExecutionContext
    {
        if (!$this->context instanceof ExecutionContext) {
            $this->context = $this->createExecutionContext();
        }

        return $this->context;
    }

    /**
     * Get the project associated with the local configuration file.
     */
    protected function getProject(): Project
    {
        return $this->getContext()->getProjectOrFail();
    }

    /**
     * Get the project configuration.
     */
    protected function getProjectConfiguration(): ProjectConfiguration
    {
        return $this->getContext()->getProjectConfiguration();
    }

    /**
     * Get the project directory.
     */
    protected function getProjectDirectory(): string
    {
        return $this->getContext()->getProjectDirectory();
    }

    /**
     * Get the active team.
     */
    protected function getTeam(): Team
    {
        return $this->getContext()->getTeamOrFail();
    }

    /**
     * Invoke another console command.
     */
    protected function invoke(string $command, array $arguments = [], ?OutputInterface $output = null): int
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new RuntimeException('No Application instance found');
        }

        return $application->find($command)->run(new ArrayInput($arguments), $output ?? $this->output);
    }

    /**
     * Provision a new resource using the given resource model class.
     *
     * @template T of ResourceModelInterface
     *
     * @param class-string<T> $resourceClass
     *
     * @return T|null
     */
    protected function provision(string $resourceClass, array $fulfilledRequirements = [], ?ResourceModelInterface $parent = null): ?ResourceModelInterface
    {
        $context = $this->getContext();

        if ($parent instanceof ResourceModelInterface) {
            $context = $context->withParentResource($parent);
        }

        return $context->provision($resourceClass, $fulfilledRequirements);
    }

    /**
     * Resolve an existing resource using the given resource model class.
     *
     * @template T of ResourceModelInterface
     *
     * @param class-string<T> $resourceClass
     *
     * @return T
     */
    protected function resolve(string $resourceClass, string $question, ?ResourceModelInterface $parent = null): ResourceModelInterface
    {
        $context = $this->getContext();

        if ($parent instanceof ResourceModelInterface) {
            $context = $context->withParentResource($parent);
        }

        return $context->resolve($resourceClass, $question);
    }

    /**
     * Set the project associated with the current context.
     */
    protected function setProject(Project $project): void
    {
        $this->context = $this->getContext()->withProject($project);
    }

    /**
     * Wait for the given callable to complete.
     */
    protected function wait(callable $callable, int $timeout = 60, int $sleep = 1)
    {
        if (0 !== $timeout) {
            $timeout += time();
        }

        do {
            $result = $callable();
            sleep($sleep);
        } while (empty($result) && time() < $timeout);

        return $result;
    }

    /**
     * Perform the command.
     */
    abstract protected function perform();

    /**
     * Creates the execution context.
     */
    private function createExecutionContext(): ExecutionContext
    {
        return $this->contextFactory->create($this->input, $this->output);
    }
}
