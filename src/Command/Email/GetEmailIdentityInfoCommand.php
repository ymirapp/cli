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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class GetEmailIdentityInfoCommand extends AbstractCommand
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
            ->addArgument('identity', InputArgument::REQUIRED, 'The ID or name of the email identity to fetch')
            ->setDescription('Get the information on an email identity');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $identity = $this->apiClient->getEmailIdentity($this->getStringArgument($input, 'identity'));

        $output->horizontalTable(
            ['Name', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Type', 'Status', 'Managed'],
            [[$identity['name'], new TableSeparator(), $identity['provider']['name'], $identity['region'], new TableSeparator(), $identity['type'], $identity['status'], $identity['managed'] ? 'yes' : 'no']]
        );

        if (!isset($identity['managed'], $identity['type'], $identity['validation_record']['name'], $identity['validation_record']['value']) || 'domain' !== $identity['type'] || true === $identity['managed']) {
            return;
        }

        $output->newLine();
        $output->warn('The following DNS record needs to be exist on your DNS server at all times to keep the email identity active:');
        $output->newLine();
        $output->table(
            ['Name', 'Value'],
            [$identity['validation_record']]
        );
    }
}
