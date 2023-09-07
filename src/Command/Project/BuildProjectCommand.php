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

namespace Ymir\Cli\Command\Project;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Build\BuildStepInterface;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class BuildProjectCommand extends AbstractProjectCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'build';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:build';

    /**
     * The build steps to perform.
     *
     * @var BuildStepInterface[]
     */
    private $buildSteps;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, iterable $buildSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        foreach ($buildSteps as $buildStep) {
            $this->addBuildStep($buildStep);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Build the project for deployment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to build', 'staging')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Run the build in debug mode')
            ->addOption('with-uploads', null, InputOption::VALUE_NONE, 'Copy the "uploads" directory during the build');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $buildOptions = [
            'debug' => $this->getBooleanOption($input, 'debug'),
            'environment' => $this->getStringArgument($input, 'environment'),
            'uploads' => $this->getBooleanOption($input, 'with-uploads'),
        ];

        $output->info('Building project');

        collect($this->buildSteps)->filter(function (BuildStepInterface $buildStep) use ($buildOptions) {
            return $buildStep->isNeeded($buildOptions, $this->projectConfiguration);
        })->each(function (BuildStepInterface $buildStep) use ($buildOptions, $output) {
            $output->writeStep($buildStep->getDescription());
            $buildStep->perform($buildOptions['environment'], $this->projectConfiguration);
        });

        $output->info('Project built successfully');
    }

    /**
     * Add a build step to the command.
     */
    private function addBuildStep(BuildStepInterface $buildStep)
    {
        $this->buildSteps[] = $buildStep;
    }
}
