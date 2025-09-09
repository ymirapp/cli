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

use Illuminate\Support\Arr;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabasesRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): array
    {
        if (!Arr::has($fulfilledRequirements, ['server', 'user']) || !$fulfilledRequirements['server'] instanceof DatabaseServer) {
            throw new RequirementDependencyException('"server" and "user" must be fulfilled before fulfilling the databases requirement');
        }

        $databases = $context->getInput()->getArrayArgument('databases', false);

        if (!empty($databases)) {
            return $databases;
        }

        return $fulfilledRequirements['server']->isPublic() && !$context->getOutput()->confirm(sprintf('Do you want the "<comment>%s</comment>" user to have access to all databases?', $fulfilledRequirements['user']), false)
             ? $context->getOutput()->multichoice(sprintf('Which databases should the "<comment>%s</comment>" database user have access to? (Use a comma-separated list)', $fulfilledRequirements['user']), $context->getApiClient()->getDatabases($fulfilledRequirements['server'])->map(function (Database $database) {
                 return $database->getName();
             }))
             : [];
    }
}
