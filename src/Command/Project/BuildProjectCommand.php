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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Build\BuildStepInterface;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class BuildProjectCommand extends AbstractCommand
{
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
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, iterable $buildSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration);

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
            ->setAliases(['build'])
            ->setDescription('Build the project for deployment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = $input->getArgument('environment');

        if (!is_string($environment)) {
            throw new RuntimeException('Invalid "environment" argument given');
        }

        $output->info('Building project');

        foreach ($this->buildSteps as $buildStep) {
            $output->writeStep($buildStep->getDescription());
            $buildStep->perform($environment);
        }

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
