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

use Illuminate\Support\Collection;
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Type\ProjectTypeInterface;

class ProjectTypeRequirement extends AbstractRequirement
{
    /**
     * The project types.
     *
     * @var Collection
     */
    private $projectTypes;

    /**
     * Constructor.
     */
    public function __construct(array $projectTypes, string $question)
    {
        parent::__construct($question);

        $this->projectTypes = collect($projectTypes);
    }

    /**
     * {@inheritDoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): ProjectTypeInterface
    {
        $projectType = $this->projectTypes->first(function (ProjectTypeInterface $projectType) use ($context) {
            return $projectType->matchesProject($context->getProjectDirectory());
        });

        if ($projectType instanceof ProjectTypeInterface) {
            return $projectType;
        }

        $selectedProjectType = $context->getOutput()->choice($this->question, $this->projectTypes->map(function (ProjectTypeInterface $projectType): string {
            return $projectType->getName();
        }));

        $projectType = $this->projectTypes->first(function (ProjectTypeInterface $projectType) use ($selectedProjectType) {
            return $selectedProjectType === $projectType->getName();
        });

        if (!$projectType instanceof ProjectTypeInterface) {
            throw new RequirementFulfillmentException('No project type found');
        }

        return $projectType;
    }
}
