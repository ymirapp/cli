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

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;

abstract class AbstractCertificateCommand extends AbstractCommand
{
    /**
     * Get the "certificate" argument.
     */
    protected function getCertificateArgument(InputInterface $input): int
    {
        $certificateId = $this->getStringArgument($input, 'certificate');

        if (!is_numeric($certificateId)) {
            throw new InvalidArgumentException('The "certificate" argument must be the ID of the SSL certificate');
        }

        return (int) $certificateId;
    }

    /**
     * Parse the certificate details for the certificate validation DNS records.
     */
    protected function parseCertificateValidationRecords($certificate): array
    {
        return !empty($certificate['domains'])
             ? collect($certificate['domains'])
                ->where('managed', false)
                ->pluck('validation_record')
                ->filter()
                ->unique(function (array $validationRecord) {
                    return $validationRecord['name'].$validationRecord['value'];
                })
                ->map(function (array $validationRecord) {
                    return ['CNAME', $validationRecord['name'], $validationRecord['value']];
                })
                ->all()
             : [];
    }
}
