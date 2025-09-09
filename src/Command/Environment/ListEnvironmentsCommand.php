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
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class ListEnvironmentsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the project environments')
            ->addArgument('project', InputArgument::OPTIONAL, 'The ID or name of the project to list the environments of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $project = $this->resolve(Project::class, 'Which project would you like to list the environments for?');

        $this->output->table(
            ['Id', 'Name', 'URL'],
            $this->apiClient->getEnvironments($project)->map(function (Environment $environment) {
                return [$environment->getId(), $environment->getName(), 'https://'.$environment->getVanityDomainName()];
            })->all()
        );
    }
}
