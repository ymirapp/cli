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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\EmailIdentity;

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
            ->setDescription('Get the information on an email identity')
            ->addArgument('identity', InputArgument::OPTIONAL, 'The ID or name of the email identity to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $identity = $this->resolve(EmailIdentity::class, 'Which email identity would you like to get information about?');

        $this->output->horizontalTable(
            ['Name', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Type', 'Verified', 'Managed'],
            [[$identity->getName(), new TableSeparator(), $identity->getProvider()->getName(), $identity->getRegion(), new TableSeparator(), $identity->getType(), $this->output->formatBoolean($identity->isVerified()), $this->output->formatBoolean($identity->isManaged())]]
        );

        $this->displayDkimAuthenticationRecords($identity);
    }

    /**
     * Display warning about DNS records required to authenticate the DKIM signature and verify it.
     */
    private function displayDkimAuthenticationRecords(EmailIdentity $identity): void
    {
        $dkimRecords = $identity->getDkimAuthenticationRecords();

        if (empty($dkimRecords) || $identity->isManaged()) {
            return;
        }

        $this->output->newLine();
        $this->output->important('The following DNS records needs to exist on your DNS server at all times to verify the email identity and authenticate its DKIM signature:');
        $this->output->newLine();
        $this->output->table(
            ['Name', 'Type', 'Value'],
            collect($dkimRecords)->map(function (array $dkimRecord) {
                return [
                    $dkimRecord['name'],
                    strtoupper($dkimRecord['type']),
                    $dkimRecord['value'],
                ];
            })->all()
        );
    }
}
