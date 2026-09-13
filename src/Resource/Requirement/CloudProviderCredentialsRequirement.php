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

namespace Ymir\Cli\Resource\Requirement;

use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\CloudProvider;

class CloudProviderCredentialsRequirement implements RequirementInterface
{
    /**
     * The pending cloud provider being connected.
     *
     * @var CloudProvider
     */
    private $provider;

    /**
     * Constructor.
     */
    public function __construct(CloudProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): array
    {
        if (!array_key_exists('authentication_method', $fulfilledRequirements)) {
            throw new RequirementDependencyException('"authentication_method" must be fulfilled before fulfilling the cloud provider credentials requirement');
        }

        switch ($fulfilledRequirements['authentication_method']) {
            case CloudProviderAuthenticationMethodRequirement::ACCESS_KEY:
                $context->getOutput()->warning('Access keys are a legacy, less-secure authentication method. AssumeRole is recommended.');

                return (new AwsAccessKeyRequirement())->fulfill($context);
            case CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE:
                return (new AwsAssumeRoleRequirement($this->provider))->fulfill($context);
            default:
                throw new RequirementValidationException(sprintf('The cloud provider authentication method must be one of: %s', implode(', ', CloudProviderAuthenticationMethodRequirement::METHODS)));
        }
    }
}
