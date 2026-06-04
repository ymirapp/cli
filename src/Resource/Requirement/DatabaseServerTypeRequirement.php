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

use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Network;

class DatabaseServerTypeRequirement extends AbstractDatabaseServerRequirement
{
    /**
     * The default type value.
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

        $this->default = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): string
    {
        if (empty($fulfilledRequirements['engine']) || !is_string($fulfilledRequirements['engine'])) {
            throw new RequirementDependencyException('"engine" must be fulfilled before fulfilling the database server type requirement');
        }

        $input = $context->getInput();
        $type = $input->getStringOption('type');

        if (!empty($fulfilledRequirements['serverless'])) {
            return $this->getAuroraDatabaseTypeForEngine($fulfilledRequirements['engine']);
        }

        if (null !== $type && $this->isAuroraDatabaseType($type)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid database type', $type));
        }

        if (empty($fulfilledRequirements['network']) || !$fulfilledRequirements['network'] instanceof Network) {
            throw new RequirementDependencyException('"network" must be fulfilled before fulfilling the database server type requirement');
        }

        $types = $context->getApiClient()->getDatabaseServerTypes($fulfilledRequirements['network']->getProvider())->filter(function ($description, string $type): bool {
            return !$this->isAuroraDatabaseType($type);
        });

        if ($types->isEmpty()) {
            throw new RequirementFulfillmentException('No database server types found');
        } elseif (null !== $type && !$types->has($type)) {
            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid database type', $type));
        } elseif (null === $type) {
            $type = $context->getOutput()->choice($this->question, $types, $this->default);
        }

        return $type;
    }
}
