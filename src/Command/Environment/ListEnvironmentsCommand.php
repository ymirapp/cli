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

use Ymir\Cli\Command\AbstractProjectCommand;

class ListEnvironmentsCommand extends AbstractProjectCommand
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
            ->setDescription('List the project\'s environments');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->table(
            ['Id', 'Name', 'URL'],
            $this->apiClient->getEnvironments($this->projectConfiguration->getProjectId())->map(function (array $environment) {
                return [$environment['id'], $environment['name'], 'https://'.$environment['vanity_domain_name']];
            })->all()
        );
    }
}
