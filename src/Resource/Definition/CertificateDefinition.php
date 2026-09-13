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

namespace Ymir\Cli\Resource\Definition;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\Certificate\GetCertificateInfoCommand;
use Ymir\Cli\Command\Certificate\RequestCertificateCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\FinalizationFailedException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Certificate;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\CertificateDomainsRequirement;
use Ymir\Cli\Resource\Requirement\ConnectedCloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;
use Ymir\Cli\Support\WaitsForResultTrait;

class CertificateDefinition implements FinalizableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    use WaitsForResultTrait;

    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Certificate::class;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(ExecutionContext $context, ResourceModelInterface $resource, array $fulfilledRequirements): ResourceModelInterface
    {
        if (!$resource instanceof Certificate) {
            throw new LogicException('SSL certificate provisioning must return an SSL certificate');
        }

        $isManaged = collect($resource->getDomains())->contains('managed', true);
        $validationRecords = [];

        if (!$isManaged) {
            $validationRecords = $this->getValidationRecords($context->getApiClient(), $resource);
        }

        $output = $context->getOutput();
        $output->info('SSL certificate requested');

        if ($isManaged) {
            return $resource;
        }

        $output->newLine();
        $output->important('The following DNS record(s) need to be manually added to your DNS server to validate the SSL certificate:');
        $output->newLine();
        $output->table(
            ['Type', 'Name', 'Value'],
            $validationRecords
        );
        $output->warning('The SSL certificate won\'t be issued until these DNS record(s) are added');

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'domains' => new CertificateDomainsRequirement('Which domains should the SSL certificate have? (Use a comma-separated list)'),
            'provider' => new ConnectedCloudProviderRequirement('Which cloud provider would you like to request the SSL certificate on?'),
            'region' => new RegionRequirement('Which region should the SSL certificate be created in?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'SSL certificate';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createCertificate($fulfilledRequirements['provider'], $fulfilledRequirements['domains'], $fulfilledRequirements['region']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): Certificate
    {
        $input = $context->getInput();
        $certificateId = $input->getStringArgument('certificate');

        $certificates = $context->getApiClient()->getCertificates($context->getTeam());

        if ($certificates->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no SSL certificates, but you can request one with the "%s" command', RequestCertificateCommand::NAME));
        } elseif (empty($certificateId)) {
            $certificateId = $context->getOutput()->choice($question, $certificates->mapWithKeys(function (Certificate $certificate) {
                $domains = collect($certificate->getDomains())->pluck('domain_name')->implode(', ');

                return [$certificate->getId() => sprintf('%d: %s (%s)', $certificate->getId(), $domains, $certificate->getRegion())];
            })->all());
        }

        if (empty($certificateId)) {
            throw new InvalidInputException('You must provide a valid SSL certificate ID');
        }

        $resolvedCertificate = $certificates->firstWhereIdOrName($certificateId);

        if (!$resolvedCertificate instanceof Certificate) {
            throw new ResourceNotFoundException($this->getResourceName(), $certificateId);
        }

        return $resolvedCertificate;
    }

    /**
     * Get the DNS validation records, polling because AWS generates them after certificate creation.
     */
    private function getValidationRecords(ApiClient $apiClient, Certificate $certificate): array
    {
        try {
            $validationRecords = $this->wait(function () use ($apiClient, $certificate): array {
                return $apiClient->getCertificate($certificate->getId())->getValidationRecords();
            });
        } catch (\Throwable $exception) {
            throw new FinalizationFailedException(sprintf('Failed to fetch the DNS validation records for the SSL certificate (ID: %1$d): %2$s. You can retrieve them later with the "%3$s %1$d" command', $certificate->getId(), $exception->getMessage(), GetCertificateInfoCommand::NAME), $exception->getCode(), $exception);
        }

        if (empty($validationRecords)) {
            throw new FinalizationFailedException(sprintf('Timed out waiting for the DNS validation records for the SSL certificate (ID: %1$d). You can retrieve them later with the "%2$s %1$d" command', $certificate->getId(), GetCertificateInfoCommand::NAME));
        }

        return $validationRecords;
    }
}
