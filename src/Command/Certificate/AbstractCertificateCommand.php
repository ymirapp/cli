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

abstract class AbstractCertificateCommand extends AbstractCommand
{
    /**
     * Get the list of domains from the certificate.
     */
    protected function getDomainsList($certificate): string
    {
        return !empty($certificate['domains']) ? implode(PHP_EOL, collect($certificate['domains'])->pluck('name')->all()) : '';
    }

    /**
     * Parse the certificate details for the certificate validation DNS records.
     */
    protected function parseCertificateValidationRecords($certificate): array
    {
        return !empty($certificate['domains'])
             ? collect($certificate['domains'])
                ->pluck('validation_record')
                ->where('managed', false)
                ->filter()
                ->unique(function (array $validationRecord) {
                    return $validationRecord['name'].$validationRecord['value'];
                })
                ->all()
             : [];
    }
}
