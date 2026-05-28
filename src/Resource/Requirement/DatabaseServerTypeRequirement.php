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
use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\Network;

class DatabaseServerTypeRequirement extends AbstractDatabaseServerRequirement
{
    /**
     * The Aurora database types mapped by database server engine.
     *
     * @var array
     */
    private const AURORA_TYPES = [
        DatabaseServer::ENGINE_MYSQL => DatabaseServer::AURORA_MYSQL_DATABASE_TYPE,
        DatabaseServer::ENGINE_POSTGRESQL => DatabaseServer::AURORA_POSTGRESQL_DATABASE_TYPE,
    ];

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

        $engine = $fulfilledRequirements['engine'];
        $input = $context->getInput();

        if ($input->getBooleanOption('serverless')) {
            return $this->getAuroraDatabaseTypeForEngine($engine);
        }

        $type = $input->getStringOption('type');

        if (null !== $type && $this->isAuroraDatabaseType($type)) {
            if ($input->hasOption('serverless') && $this->isEngineCompatibleWithType($engine, $type)) {
                return $type;
            }

            throw new InvalidInputException(sprintf('The type "%s" isn\'t a valid database type', $type));
        }

        if (empty($fulfilledRequirements['network']) || !$fulfilledRequirements['network'] instanceof Network) {
            throw new RequirementDependencyException('"network" must be fulfilled before fulfilling the database server type requirement');
        }

        $types = $context->getApiClient()->getDatabaseServerTypes($fulfilledRequirements['network']->getProvider())->filter(function ($description, string $type) use ($engine): bool {
            return !$this->isAuroraDatabaseType($type) && $this->isEngineCompatibleWithType($engine, $type);
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

    /**
     * Get the Aurora database type for the given database engine.
     */
    private function getAuroraDatabaseTypeForEngine(string $engine): string
    {
        if (!isset(self::AURORA_TYPES[$engine])) {
            throw new UnsupportedDatabaseServerEngineException($engine);
        }

        return self::AURORA_TYPES[$engine];
    }

    /**
     * Check if the database server engine is compatible with the database server type.
     */
    private function isEngineCompatibleWithType(string $engine, string $type): bool
    {
        if (!DatabaseServer::isEngine($engine)) {
            return false;
        }

        return !$this->isAuroraDatabaseType($type) || $this->getAuroraDatabaseTypeForEngine($engine) === $type;
    }
}
