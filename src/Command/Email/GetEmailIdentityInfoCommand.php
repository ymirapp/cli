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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\OutputInterface;

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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $identity = $this->determineEmailIdentity('Which email identity would you like to get information about', $input, $output);

        $output->horizontalTable(
            ['Name', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Type', 'Status', 'Managed'],
            [[$identity['name'], new TableSeparator(), $identity['provider']['name'], $identity['region'], new TableSeparator(), $identity['type'], $identity['status'], $output->formatBoolean($identity['managed'])]]
        );

        if ('domain' !== $identity['type'] || true === $identity['managed']) {
            return;
        }

        if (!empty($identity['validation_record'])) {
            $output->newLine();
            $output->important('The following DNS record needs to exist on your DNS server at all times to keep the email identity active:');
            $output->newLine();
            $output->table(
                ['Name', 'Type', 'Value'],
                [$identity['validation_record']]
            );
        }

        if (!empty($identity['dkim_authentication_records'])) {
            $output->newLine();
            $output->important('The following DNS records needs to exist on your DNS server at all times to authenticate the DKIM signature of the email identity:');
            $output->newLine();
            $output->table(
                ['Name', 'Type', 'Value'],
                $identity['dkim_authentication_records']
            );
        }
    }
}
