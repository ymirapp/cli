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

class ResolveOrProvisionPublicDatabaseServerRequirement extends ResolveOrProvisionDatabaseServerRequirement
{
    /**
     * Constructor.
     */
    public function __construct(string $question)
    {
        parent::__construct($question, ['private' => false]);
    }
}
