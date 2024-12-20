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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RequestCertificateCommand extends AbstractCertificateCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:request';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Request a new SSL certificate')
            ->addArgument('domains', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'List of domains that the SSL certificate is for')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the certificate will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the certificate will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $domains = $this->input->getArrayArgument('domains');

        if (empty($domains)) {
            $domains = array_map('trim', explode(',', (string) $this->output->ask('Please enter a comma-separated list of domains for the certificate')));
        }

        if (1 === count($domains) && false === stripos($domains[0], '*.') && $this->output->confirm(sprintf('Do you want your certificate to also cover "<comment>*.%s</comment>" subdomains?', $domains[0]))) {
            $domains[] = '*.'.$domains[0];
        } elseif (1 === count($domains) && 0 === stripos($domains[0], '*.')) {
            $domains[] = substr($domains[0], 2);
        }

        $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider where the SSL certificate will be created');

        $certificate = $this->apiClient->createCertificate($providerId, $domains, $this->determineRegion('Enter the name of the region where the SSL certificate will be created', $providerId));

        $isManaged = collect($certificate['domains'])->contains('managed', true);
        $validationRecords = [];

        if (!$isManaged) {
            $validationRecords = $this->wait(function () use ($certificate) {
                return $this->parseCertificateValidationRecords($this->apiClient->getCertificate($certificate['id']));
            });
        }

        $this->output->info('SSL certificate requested');

        if (!$isManaged && !empty($validationRecords)) {
            $this->output->newLine();
            $this->output->important('The following DNS record(s) need to be manually added to your DNS server to validate the SSL certificate:');
            $this->output->newLine();
            $this->output->table(
                ['Type', 'Name', 'Value'],
                $validationRecords
            );
            $this->output->warning('The SSL certificate won\'t be issued until these DNS record(s) are added');
        } elseif (!$isManaged && empty($validationRecords)) {
            $this->output->newLine();
            $this->output->warning(sprintf('Unable to fetch the DNS record(s) to your DNS server to validate the SSL certificate. You can run the "<comment>%s</comment>" command to get them.', GetCertificateInfoCommand::NAME));
        }
    }
}
