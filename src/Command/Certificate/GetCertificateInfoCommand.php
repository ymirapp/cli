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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Certificate;

class GetCertificateInfoCommand extends AbstractCommand
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
            ->addArgument('certificate', InputArgument::OPTIONAL, 'The ID of the SSL certificate to fetch the information of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $certificate = $this->resolve(Certificate::class, 'Which SSL certificate would you like to get information about?');

        $this->output->horizontalTable(
            ['Domains', new TableSeparator(), 'Provider', 'Region', new TableSeparator(), 'Status', 'In Use'],
            [[implode(PHP_EOL, $this->getDomainNames($certificate->getDomains())), new TableSeparator(), $certificate->getProvider()->getName(), $certificate->getRegion(), new TableSeparator(), $certificate->getStatus(), $this->output->formatBoolean($certificate->isInUse())]]
        );

        $validationRecords = $certificate->getValidationRecords();

        if (!empty($validationRecords)) {
            $this->output->newLine();
            $this->output->important('The following DNS record(s) need to exist on your DNS server at all times:');
            $this->output->newLine();
            $this->output->table(
                ['Type', 'Name', 'Value'],
                $validationRecords
            );
            $this->output->warning('The SSL certificate won\'t be issued or renewed if these DNS record(s) don\'t exist.');
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
