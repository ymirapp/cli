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

namespace Ymir\Cli\Resource\Definition;

interface ResourceDefinitionInterface
{
    /**
     * Get the class name of the resource model that the definition represents.
     *
     * Used by the dependency injection service locator.
     */
    public static function getModelClass(): string;

    /**
     * Get the user-friendly name of the resource model.
     */
    public function getResourceName(): string;
}
