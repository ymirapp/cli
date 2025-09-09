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

use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

class ResolveOrProvisionResourceRequirement extends ResourceRequirement
{
    /**
     * Pre-fulfilled requirements to pass to the provisioner.
     *
     * @var array
     */
    private $preFulfilledRequirements;

    /**
     * Constructor.
     */
    public function __construct(ProvisionableResourceDefinitionInterface $resource, string $question, array $preFulfilledRequirements = [])
    {
        if (!$resource instanceof ResolvableResourceDefinitionInterface) {
            throw new LogicException('Resource definition must implement ResolvableResourceDefinitionInterface');
        }

        parent::__construct($resource, $question);

        $this->preFulfilledRequirements = $preFulfilledRequirements;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ResourceModelInterface
    {
        if (!$this->resource instanceof ProvisionableResourceDefinitionInterface) {
            throw new LogicException('Resource definition must implement ProvisionableResourceDefinitionInterface');
        }

        try {
            return parent::fulfill($context, $fulfilledRequirements);
        } catch (NoResourcesFoundException $exception) {
            return $context->getProvisioner()->provision($this->resource, $context, $this->preFulfilledRequirements);
        }
    }
}
