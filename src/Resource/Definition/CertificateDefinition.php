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

use Ymir\Cli\Command\Certificate\RequestCertificateCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Certificate;

class CertificateDefinition implements ResolvableResourceDefinitionInterface
{
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
    public function getResourceName(): string
    {
        return 'SSL certificate';
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): Certificate
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
}
