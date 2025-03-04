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

namespace Ymir\Cli\Command\Email;

class ListEmailIdentitiesCommand extends AbstractEmailIdentityCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'email:identity:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the email identities that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->table(
            ['Id', 'Name', 'Type', 'Provider', 'Region', 'Verified', 'Managed'],
            $this->apiClient->getEmailIdentities($this->cliConfiguration->getActiveTeamId())->map(function (array $identity) {
                return [$identity['id'], $identity['name'], $identity['type'], $identity['provider']['name'], $identity['region'], $this->output->formatBoolean($identity['verified']), $this->output->formatBoolean($identity['managed'])];
            })->all()
        );
    }
}
