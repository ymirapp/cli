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

use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\CloudProvider;

class AwsAssumeRoleRequirement implements RequirementInterface
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
        $setupValues = collect([
            'Ymir AWS account ID' => $this->provider->getAssumeRoleYmirAccountId(),
            'External ID' => $this->provider->getAssumeRoleExternalId(),
            'Role name' => $this->provider->getAssumeRoleRoleName(),
        ]);

        if ($setupValues->filter()->count() !== $setupValues->count()) {
            throw new RequirementFulfillmentException('The Ymir API did not return the setup values needed to create the IAM role');
        }

        $output = $context->getOutput();
        $output->writeln('Create the AWS IAM role using these values:');

        $setupValues->each(function (string $value, string $label) use ($output): void {
            $output->writeln(sprintf('%s: %s', $label, $value));
        });

        return ['role_arn' => (new StringArgumentRequirement('role_arn', 'What is the AWS IAM role ARN?'))->fulfill($context)];
    }
}
