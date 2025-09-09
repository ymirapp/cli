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

namespace Ymir\Cli\Resource\Model;

interface ResourceModelInterface
{
    /**
     * Create a resource from the given array.
     */
    public static function fromArray(array $data);

    /**
     * Get the ID of the resource.
     */
    public function getId(): int;

    /**
     * Get the name of the resource.
     */
    public function getName(): string;
}
