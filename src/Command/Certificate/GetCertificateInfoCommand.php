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
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\OutputStyle;

class GetCertificateInfoCommand extends AbstractCertificateCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('certificate', InputArgument::REQUIRED, 'The ID of the SSL certificate to fetch')
            ->setDescription('Get the information on an SSL certificate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $certificateId = $this->getStringArgument($input, 'certificate');

        if (!is_numeric($certificateId)) {
            throw new InvalidArgumentException('The "certificate" argument must be the ID of the SSL certificate');
        }

        $certificate = $this->apiClient->getCertificate((int) $certificateId);

        $output->horizontalTable(
            ['Domains', new TableSeparator(), 'Status', 'Region', 'In Use'],
            [[implode(PHP_EOL, $this->getDomainNames($certificate['domains'])), new TableSeparator(), $certificate['status'], $certificate['region'], $certificate['in_use'] ? 'yes' : 'no']]
        );

        $validationRecords = $this->parseCertificateValidationRecords($certificate);

        if (!empty($validationRecords)) {
            $output->newLine();
            $output->warn('The following DNS record(s) need to be exist on your DNS server at all times:');
            $output->newLine();
            $output->table(
                ['Name', 'Value'],
                $validationRecords
            );
            $output->warn('The SSL certificate won\'t be issued or renewed if these DNS record(s) don\'t exist.');
        }
    }

    /**
     * Get the formatted domain names for a certificate.
     */
    private function getDomainNames(array $certificateDomains): array
    {
        return collect($certificateDomains)->map(function (array $domain) {
            return sprintf('%s (%s)', $domain['name'], $domain['validated'] ? '<fg=green>validated</>' : '<fg=red>not validated</>');
        })->all();
    }
}
