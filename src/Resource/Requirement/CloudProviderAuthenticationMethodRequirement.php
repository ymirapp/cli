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
        $methods = [
            self::ASSUME_ROLE => 'IAM role (recommended)',
            self::ACCESS_KEY => 'Access key (legacy, less secure)',
        ];

        return (string) array_search($context->getOutput()->choice($this->question, array_values($methods), $methods[$this->default]), $methods, true);
    }
}
