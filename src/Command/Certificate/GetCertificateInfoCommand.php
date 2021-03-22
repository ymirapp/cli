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

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

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
            ->setDescription('Get information on an SSL certificate')
            ->addArgument('certificate', InputArgument::REQUIRED, 'The ID of the SSL certificate to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $certificate = $this->apiClient->getCertificate($this->getCertificateArgument($input));

        $output->horizontalTable(
            ['Domains', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Status', 'In Use'],
            [[implode(PHP_EOL, $this->getDomainNames($certificate['domains'])), new TableSeparator(), $certificate['provider']['name'], $certificate['region'], new TableSeparator(), $certificate['status'], $certificate['in_use'] ? 'yes' : 'no']]
        );

        $validationRecords = $this->parseCertificateValidationRecords($certificate);

        if (!empty($validationRecords)) {
            $output->newLine();
            $output->warn('The following DNS record(s) need to exist on your DNS server at all times:');
            $output->newLine();
            $output->table(
                ['Type', 'Name', 'Value'],
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
            return sprintf('%s (%s)', $domain['domain_name'], $domain['validated'] ? '<fg=green>validated</>' : '<fg=red>not validated</>');
        })->all();
    }
}
