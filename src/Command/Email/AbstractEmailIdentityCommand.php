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

use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

abstract class AbstractEmailIdentityCommand extends AbstractCommand
{
    /**
     * Determine the email identity that the command is interacting with.
     */
    protected function determineEmailIdentity(string $question, Input $input, Output $output): array
    {
        $identity = null;
        $identities = $this->apiClient->getEmailIdentities($this->cliConfiguration->getActiveTeamId());
        $identityIdOrName = $input->getStringArgument('identity');

        if ($identities->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no email identities. You can create one with the "%s" command.', CreateEmailIdentityCommand::NAME));
        }

        if (empty($identityIdOrName)) {
            $identityIdOrName = (string) $output->choice($question, $identities->pluck('name'));
        }

        if (is_numeric($identityIdOrName)) {
            $identity = $identities->firstWhere('id', $identityIdOrName);
        } elseif (is_string($identityIdOrName)) {
            $identity = $identities->firstWhere('name', $identityIdOrName);
        }

        if (empty($identity['id'])) {
            throw new RuntimeException(sprintf('Unable to find an email identity with "%s" as the ID or name', $identityIdOrName));
        }

        return $identity;
    }

    /**
     * Display warning about DNS records required to authenticate the DKIM signature and verify it.
     */
    protected function displayDkimAuthenticationRecords(array $identity, Output $output)
    {
        if (empty($identity['dkim_authentication_records']) || $identity['managed']) {
            return;
        }

        $output->newLine();
        $output->important('The following DNS records needs to exist on your DNS server at all times to verify the email identity and authenticate its DKIM signature:');
        $output->newLine();
        $output->table(
            ['Name', 'Type', 'Value'],
            collect($identity['dkim_authentication_records'])->map(function (array $dkimRecord) {
                $dkimRecord['type'] = strtoupper($dkimRecord['type']);

                return $dkimRecord;
            })->all()
        );
    }
}
