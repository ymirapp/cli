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

namespace Ymir\Cli\Command\Provider;

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;

class ListProvidersCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:list';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the cloud provider connections belonging to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(): void
    {
        $providers = $this->apiClient->getProviders($this->getTeam());

        $this->output->info('The following cloud provider connections belong to your team:');

        $this->output->table(
            ['Id', 'Name', 'Status', 'Authentication'],
            $providers->map(function (CloudProvider $provider) {
                return [
                    $provider->getId(),
                    $provider->getName(),
                    $provider->getStatus(),
                    $provider->getAuthenticationMethod() ?? 'Unavailable',
                ];
            })->all()
        );
    }
}
