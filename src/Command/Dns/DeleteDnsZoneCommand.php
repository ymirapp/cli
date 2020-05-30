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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class DeleteDnsZoneCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('zone', InputArgument::REQUIRED, 'The ID or name of the DNS zone to delete')
            ->setDescription('Create an existing DNS zone');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $idOrName = $input->getArgument('zone');

        if (null === $idOrName || is_array($idOrName)) {
            throw new RuntimeException('The "zone" argument must be a string value');
        }

        $zoneId = $this->getZoneId($idOrName);

        if ($input->isInteractive() && !$output->confirm('Are you sure you want to delete this DNS zone?', false)) {
            return;
        }

        $this->apiClient->deleteDnsZone($zoneId);

        $output->info('DNS zone deleted successfully');
    }

    /**
     * Get the DNS zone ID from the given DNS zone ID or name.
     */
    private function getZoneId(string $idOrName): int
    {
        $zone = null;
        $zones = $this->apiClient->getDnsZones($this->cliConfiguration->getActiveTeamId());

        if (is_numeric($idOrName)) {
            $zone = $zones->firstWhere('id', $idOrName);
        } elseif (is_string($idOrName)) {
            $zone = $zones->firstWhere('name', $idOrName);
        }

        if (!is_array($zone) || !isset($zone['id']) || !is_numeric($zone['id'])) {
            throw new RuntimeException(sprintf('Unable to find a DNS zone with "%s" as the ID or name', $idOrName));
        }

        return (int) $zone['id'];
    }
}
