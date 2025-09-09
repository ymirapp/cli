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

namespace Ymir\Cli\Command\Team;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Resource\Model\Team;

class SelectTeamCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'team:select';

    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ExecutionContextFactory $contextFactory)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->cliConfiguration = $cliConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Select a new currently active team')
            ->addArgument('team', InputArgument::OPTIONAL, 'The ID of the team to make your currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $team = $this->resolve(Team::class, 'Which team would you like to switch to?');

        $this->cliConfiguration->setActiveTeamId($team->getId());

        $this->output->infoWithValue('Your active team is now', $team->getName());
    }
}
