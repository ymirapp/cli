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

namespace Placeholder\Cli\Command\Provider;

use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;

class ListCommand extends AbstractCommand
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
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $providers = $this->apiClient->getProviders($this->getActiveTeamId());

        $output->writeln("<info>The following cloud providers are connect your team:</info>\n");

        $output->table(
            ['Id', 'Name', 'Provider'],
            $providers->map(function (array $provider) {
                return [
                    $provider['id'],
                    $provider['name'],
                    $provider['provider'],
                ];
            })->all()
        );
    }
}
