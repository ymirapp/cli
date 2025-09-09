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

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\EmailIdentity;

class ListEmailIdentitiesCommand extends AbstractCommand
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
            $this->apiClient->getEmailIdentities($this->getTeam())->map(function (EmailIdentity $identity) {
                return [$identity->getId(), $identity->getName(), $identity->getType(), $identity->getProvider()->getName(), $identity->getRegion(), $this->output->formatBoolean($identity->isVerified()), $this->output->formatBoolean($identity->isManaged())];
            })->all()
        );
    }
}
