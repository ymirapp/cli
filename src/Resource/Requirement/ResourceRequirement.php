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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\ResourceModelInterface;

class ResourceRequirement extends AbstractRequirement
{
    /**
     * The required resource.
     *
     * @var ResolvableResourceDefinitionInterface
     */
    protected $resource;

    /**
     * Constructor.
     */
    public function __construct(ResolvableResourceDefinitionInterface $resource, string $question)
    {
        parent::__construct($question);

        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ResourceModelInterface
    {
        $resolvedResource = $this->resource->resolve($context, $this->question, $fulfilledRequirements);

        if (!$resolvedResource instanceof ResourceModelInterface) {
            throw new LogicException('Required resource definition must return a resource model instance');
        }

        return $resolvedResource;
    }
}
