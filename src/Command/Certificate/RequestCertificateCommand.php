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
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function perform(Input $input, Output $output)
    {
        $domains = $input->getArrayArgument('domains');

        if (empty($domains)) {
            $domains = array_map('trim', explode(',', (string) $output->ask('Please enter a comma-separated list of domains for the certificate')));
        }

        if (1 === count($domains) && false === stripos($domains[0], '*.') && $output->confirm(sprintf('Do you want your certificate to also cover "<comment>*.%s</comment>" subdomains?', $domains[0]))) {
            $domains[] = '*.'.$domains[0];
        } elseif (1 === count($domains) && 0 === stripos($domains[0], '*.')) {
            $domains[] = substr($domains[0], 2);
        }

        $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider where the SSL certificate will be created', $input, $output);

        $certificate = $this->apiClient->createCertificate($providerId, $domains, $this->determineRegion('Enter the name of the region where the SSL certificate will be created', $providerId, $input, $output));

        $isManaged = collect($certificate['domains'])->contains('managed', true);
        $validationRecords = [];

        if (!$isManaged) {
            $validationRecords = $this->wait(function () use ($certificate) {
                return $this->parseCertificateValidationRecords($this->apiClient->getCertificate($certificate['id']));
            });
        }

        $output->info('SSL certificate requested');

        if (!$isManaged && !empty($validationRecords)) {
            $output->newLine();
            $output->important('The following DNS record(s) need to be manually added to your DNS server to validate the SSL certificate:');
            $output->newLine();
            $output->table(
                ['Type', 'Name', 'Value'],
                $validationRecords
            );
            $output->warning('The SSL certificate won\'t be issued until these DNS record(s) are added');
        } elseif (!$isManaged && empty($validationRecords)) {
            $output->newLine();
            $output->warning(sprintf('Unable to fetch the DNS record(s) to your DNS server to validate the SSL certificate. You can run the "<comment>%s</comment>" command to get them.', GetCertificateInfoCommand::NAME));
        }
    }
}
