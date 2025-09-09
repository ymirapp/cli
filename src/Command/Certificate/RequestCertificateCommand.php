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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

class RequestCertificateCommand extends AbstractCommand
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
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The ID of the cloud provider where the certificate will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the certificate will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $domains = $this->input->getArrayArgument('domains');

        if (empty($domains)) {
            $domains = array_map('trim', explode(',', (string) $this->output->ask('Which domains should the SSL certificate have? (Use a comma-separated list)')));
        }

        if (1 === count($domains) && false === stripos($domains[0], '*.') && $this->output->confirm(sprintf('Do you want your certificate to also cover "<comment>*.%s</comment>" subdomains?', $domains[0]))) {
            $domains[] = '*.'.$domains[0];
        } elseif (1 === count($domains) && 0 === stripos($domains[0], '*.')) {
            $domains[] = substr($domains[0], 2);
        }

        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to request the SSL certificate on?');
        $region = $this->fulfill(new RegionRequirement('Which region should the SSL certificate be created in?'), ['provider' => $provider]);

        $certificate = $this->apiClient->createCertificate($provider, $domains, $region);

        $isManaged = collect($certificate->getDomains())->contains('managed', true);
        $validationRecords = [];

        if (!$isManaged) {
            $validationRecords = $this->wait(function () use ($certificate) {
                return $this->apiClient->getCertificate($certificate->getId())->getValidationRecords();
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
