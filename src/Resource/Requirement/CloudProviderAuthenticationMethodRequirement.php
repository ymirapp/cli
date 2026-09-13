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
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        $methods = [
            'IAM role (recommended)' => self::ASSUME_ROLE,
            'Access key (legacy, less secure)' => self::ACCESS_KEY,
        ];
        $choices = array_keys($methods);

        return $methods[$context->getOutput()->choice($this->question, $choices, $choices[0])];
    }
}
