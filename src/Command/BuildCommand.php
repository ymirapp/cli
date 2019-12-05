<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\Build\BuildStepInterface;
use Placeholder\Cli\CliConfiguration;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class BuildCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'build';

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
            ->setDescription('Build the project artifact')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $currentStep = 1;
        $totalSteps = count($this->buildSteps);

        $output->writeln('Building...');

        foreach ($this->buildSteps as $buildStep) {
            $output->writeln(sprintf('%s/%s: %s', $currentStep, $totalSteps, $buildStep->getDescription()));
            $buildStep->perform();
            ++$currentStep;
        }
    }

    /**
     * Add a build step to the command.
     */
    private function addBuildStep(BuildStepInterface $buildStep)
    {
        $this->buildSteps[] = $buildStep;
    }
}
