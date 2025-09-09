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

abstract class AbstractResourceModel implements ResourceModelInterface
{
    /**
     * The ID of the resource.
     *
     * @var int
     */
    private $id;

    /**
     * The name of the resource.
     *
     * @var string
     */
    private $name;

    /**
     * Constructor.
     */
    protected function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
}
