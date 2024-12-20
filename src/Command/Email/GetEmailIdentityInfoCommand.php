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

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;

class GetEmailIdentityInfoCommand extends AbstractEmailIdentityCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'email:identity:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get the information on an email identity')
            ->addArgument('identity', InputArgument::OPTIONAL, 'The ID or name of the email identity to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $identity = $this->determineEmailIdentity('Which email identity would you like to get information about');

        $this->output->horizontalTable(
            ['Name', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Type', 'Verified', 'Managed'],
            [[$identity['name'], new TableSeparator(), $identity['provider']['name'], $identity['region'], new TableSeparator(), $identity['type'], $this->output->formatBoolean($identity['verified']), $this->output->formatBoolean($identity['managed'])]]
        );

        $this->displayDkimAuthenticationRecords($identity);
    }
}
