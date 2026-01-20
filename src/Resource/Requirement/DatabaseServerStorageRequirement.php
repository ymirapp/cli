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

use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseServerStorageRequirement extends AbstractRequirement
{
    /**
     * The default storage value.
     *
     * @var string|null
     */
    private $default;

    /**
     * Constructor.
     */
    public function __construct(string $question, ?string $default = null)
    {
        parent::__construct($question);

        if (null !== $default && !is_numeric($default)) {
            throw new InvalidArgumentException('Default storage value must be a numeric value');
        }

        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ?int
    {
        if (empty($fulfilledRequirements['type'])) {
            throw new RequirementDependencyException('"type" must be fulfilled before fulfilling the database server storage requirement');
        } elseif (DatabaseServer::AURORA_DATABASE_TYPE === $fulfilledRequirements['type']) {
            return null;
        }

        $storage = (int) $context->getInput()->getNumericOption('storage');

        if (empty($storage)) {
            $storage = $context->getOutput()->ask($this->question, $this->default, function ($value): int {
                return $this->validate($value);
            });
        }

        return $this->validate($storage);
    }

    /**
     * Validate the database storage value.
     */
    private function validate($value): int
    {
        if (!is_numeric($value)) {
            throw new RequirementValidationException('The maximum allocated storage needs to be a numeric value');
        } elseif ($value <= 0) {
            throw new RequirementValidationException('The storage value must be a positive integer');
        }

        return (int) $value;
    }
}
