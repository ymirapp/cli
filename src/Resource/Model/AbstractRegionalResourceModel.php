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

abstract class AbstractRegionalResourceModel extends AbstractResourceModel implements RegionalResourceModelInterface
{
    /**
     * The region that the resource is in.
     *
     * @var string
     */
    private $region;

    /**
     * Constructor.
     */
    protected function __construct(int $id, string $name, string $region)
    {
        parent::__construct($id, $name);

        $this->region = $region;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion(): string
    {
        return $this->region;
    }
}
