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

namespace Ymir\Cli\Command\Dns;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

abstract class AbstractDnsCommand extends AbstractCommand
{
    /**
     * Determine the DNS zone that the command is interacting with.
     */
    protected function determineDnsZone(string $question, InputInterface $input, ConsoleOutput $output): array
    {
        $zone = null;
        $zones = $this->apiClient->getDnsZones($this->cliConfiguration->getActiveTeamId());
        $zoneIdOrName = $this->getStringArgument($input, 'zone');

        if ($zones->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no DNS zones. You can create one with the "%s" command.', CreateDnsZoneCommand::NAME));
        }

        if (empty($zoneIdOrName)) {
            $zoneIdOrName = (string) $output->choice($question, $zones->pluck('name')->all());
        }

        if (is_numeric($zoneIdOrName)) {
            $zone = $zones->firstWhere('id', $zoneIdOrName);
        } elseif (is_string($zoneIdOrName)) {
            $zone = $zones->firstWhere('name', $zoneIdOrName);
        }

        if (empty($zone['id'])) {
            throw new RuntimeException(sprintf('Unable to find a DNS zones with "%s" as the ID or name', $zoneIdOrName));
        }

        return $zone;
    }
}
