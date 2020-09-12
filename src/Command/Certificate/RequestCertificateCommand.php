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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain secured by the SSL certificate')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the certificate will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the certificate will be located')
            ->setDescription('Request a new SSL certificate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $domain = $this->getStringArgument($input, 'domain');

        if (0 === stripos($domain, '*.')) {
            $domain = substr($domain, 2);
        }

        $providerId = $this->determineCloudProvider($input, $output, 'Enter the ID of the cloud provider where the SSL certificate will be created');
        $certificate = $this->apiClient->createCertificate($providerId, $domain, $this->determineRegion($input, $output, $providerId, 'Enter the name of the region where the SSL certificate will be created'));
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
            $output->warn('The following DNS record(s) need to be manually added to your DNS server to validate the SSL certificate:');
            $output->newLine();
            $output->table(
                ['Name', 'Value'],
                $validationRecords
            );
            $output->warn('The SSL certificate won\'t be issued until these DNS record(s) are added');
        } elseif (!$isManaged && empty($validationRecords)) {
            $output->newLine();
            $output->warn(sprintf('Unable to fetch the DNS record(s) to your DNS server to validate the SSL certificate. You can run the "%s" command to get them.', GetCertificateInfoCommand::NAME));
        }
    }
}
