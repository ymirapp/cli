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

abstract class AbstractRequirement implements RequirementInterface
{
    /**
     * The question to ask when prompting the user for the requirement.
     *
     * @var string
     */
    protected $question;

    /**
     * Constructor.
     */
    public function __construct(string $question)
    {
        $this->question = $question;
    }
}
