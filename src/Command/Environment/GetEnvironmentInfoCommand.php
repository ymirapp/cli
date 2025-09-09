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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\RendersEnvironmentInfoTrait;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class GetEnvironmentInfoCommand extends AbstractCommand
{
    use RendersEnvironmentInfoTrait;

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information on the environment(s)')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get information on');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $project = $this->resolve(Project::class, 'Which project would you like to get environment information for?');
        $environmentName = $this->input->getStringArgument('environment');
        $environments = $this->apiClient->getEnvironments($project);

        if (!empty($environmentName) && !$environments->has($environmentName)) {
            throw new InvalidInputException(sprintf('Environment "%s" doesn\'t exist', $environmentName));
        } elseif (!empty($environmentName)) {
            $environments = $environments->filter(function (Environment $environment) use ($environmentName): bool {
                return $environmentName === $environment->getName();
            });
        }

        if (empty($environmentName)) {
            $this->output->info(sprintf('Listing information on all <comment>%s</comment> environments', $project->getName()));
        }

        $environments->each(function (Environment $environment): void {
            $this->displayEnvironmentTable($environment);
        });
    }
}
