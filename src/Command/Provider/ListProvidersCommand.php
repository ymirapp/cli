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
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the cloud provider accounts connected to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $providers = $this->apiClient->getProviders($this->cliConfiguration->getActiveTeamId());

        $output->info('The following cloud providers are connect your team:');

        $output->table(
            ['Id', 'Name'],
            $providers->map(function (array $provider) {
                return [
                    $provider['id'],
                    $provider['name'],
                ];
            })->all()
        );
    }
}
