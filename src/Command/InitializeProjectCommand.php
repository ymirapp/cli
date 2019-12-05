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

use Placeholder\Cli\Command\Provider\ConnectCommand;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;

class InitializeProjectCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'init';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Initialize a new project in the current directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $teamId = $this->getActiveTeamId();
        $providers = $this->apiClient->getProviders($teamId);

        if ($providers->isEmpty()) {
            $output->writeln('Connecting to a cloud provider');
            $this->invoke($output, ConnectCommand::NAME);
            $providers = $this->apiClient->getProviders($teamId);
        }

        $name = $output->askSlug('What is the name of this project');
        $provider = 1 === count($providers)
                    ? $providers[0]['id'] :
                    $output->choiceCollection('Enter the ID of the cloud provider that the project will use', $providers);
        $region = $output->choice('Enter the name of the region that the project will be in', $this->apiClient->getRegions($provider)->all());

        $project = $this->apiClient->createProject($provider, $name, $region);

        $output->writeln(sprintf('"<info>%s</info>" project has been initialized in the "<info>%s</info>" region', $name, $region));
    }
}
