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

use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;

class CloudProviderAuthenticationMethodRequirement extends AbstractRequirement
{
    /**
     * The access key authentication method.
     *
     * @var string
     */
    public const ACCESS_KEY = 'access_key';

    /**
     * The AssumeRole authentication method.
     *
     * @var string
     */
    public const ASSUME_ROLE = 'assume_role';

    /**
     * The available authentication methods.
     *
     * @var array
     */
    public const METHODS = [
        self::ASSUME_ROLE,
        self::ACCESS_KEY,
    ];

    /**
     * The default authentication method.
     *
     * @var string
     */
    private $default;

    /**
     * Constructor.
     */
    public function __construct(string $question, ?string $default = null)
    {
        parent::__construct($question);

        $default = $default ?? self::ASSUME_ROLE;

        if (!in_array($default, self::METHODS, true)) {
            throw new RequirementValidationException(sprintf('The "%s" cloud provider authentication method must be one of: %s', $default, implode(', ', self::METHODS)));
        }

        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        $input = $context->getInput();
        $awsProfile = $input->getStringOption('aws-profile');
        $roleArn = $input->getStringOption('role-arn');

        if (null !== $awsProfile && null !== $roleArn) {
            throw new RequirementValidationException('The "--aws-profile" and "--role-arn" options cannot be used together');
        }

        if (null !== $awsProfile) {
            return self::ACCESS_KEY;
        }

        if (null !== $roleArn) {
            return self::ASSUME_ROLE;
        }

        if (!$input->isInteractive()) {
            throw new RequirementValidationException(sprintf('You must enter the %s option when configuring cloud provider authentication non-interactively', $input->hasOption('role-arn') ? '"--aws-profile" or "--role-arn"' : '"--aws-profile"'));
        }

        $methods = [
            self::ASSUME_ROLE => 'IAM role (recommended)',
            self::ACCESS_KEY => 'Access key (legacy, less secure)',
        ];

        return (string) array_search($context->getOutput()->choice($this->question, array_values($methods), $methods[$this->default]), $methods, true);
    }
}
