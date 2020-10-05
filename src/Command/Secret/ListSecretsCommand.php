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

namespace Ymir\Cli\Command\Secret;

use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\ConsoleOutput;

class ListSecretsCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'secret:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the secrets of an environment')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $output->table(
            ['Id', 'Name', 'Last Updated'],
            $this->apiClient->getSecrets($this->projectConfiguration->getProjectId(), (string) $this->getStringOption($input, 'environment'))->map(function (array $secret) {
                return [$secret['id'], $secret['name'], Carbon::parse($secret['updated_at'])->diffForHumans()];
            })->all()
        );
    }
}
