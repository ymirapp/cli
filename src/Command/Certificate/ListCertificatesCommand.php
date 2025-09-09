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

namespace Ymir\Cli\Command\Certificate;

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Certificate;

class ListCertificatesCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the SSL certificates that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $certificates = $this->apiClient->getCertificates($this->getTeam());

        $this->output->table(
            ['Id', 'Provider', 'Region', 'Domains', 'Status', 'In Use'],
            $certificates->map(function (Certificate $certificate) {
                return [$certificate->getId(), $certificate->getProvider()->getName(), $certificate->getRegion(), $this->getDomainsList($certificate), $certificate->getStatus(), $this->output->formatBoolean($certificate->isInUse())];
            })->all()
        );
    }

    /**
     * Get the list of domains from the certificate.
     */
    private function getDomainsList(Certificate $certificate): string
    {
        return implode(PHP_EOL, collect($certificate->getDomains())->pluck('domain_name')->all());
    }
}
