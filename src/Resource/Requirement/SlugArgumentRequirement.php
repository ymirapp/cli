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

class SlugArgumentRequirement extends AbstractRequirement
{
    /**
     * The name of the required argument.
     *
     * @var string
     */
    private $argument;

    /**
     * The default answer if the users enters nothing.
     *
     * @var string|null
     */
    private $default;

    public function __construct(string $argument, string $question, ?string $default = null)
    {
        parent::__construct($question);

        $this->argument = $argument;
        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        $value = null;

        if ($context->getInput()->hasArgument($this->argument)) {
            $value = $context->getInput()->getStringArgument($this->argument);
        }

        if (empty($value)) {
            $value = $context->getOutput()->askSlug($this->question, $this->default, function ($value): string {
                return $this->validate($value);
            });
        }

        return $this->validate($value);
    }

    /**
     * Validate the string argument value.
     */
    private function validate($value): string
    {
        if (null === $value) {
            $value = '';
        }

        if (!is_string($value)) {
            throw new RequirementValidationException(sprintf('"%s" is not a valid string argument', $this->argument));
        }

        $value = trim($value);

        if ('' === $value) {
            throw new RequirementValidationException(sprintf('You must enter a "%s" argument', $this->argument));
        }

        return $value;
    }
}
