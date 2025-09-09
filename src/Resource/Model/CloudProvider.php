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

use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Support\Arr;

final class CloudProvider extends AbstractResourceModel
{
    /**
     * The team that the cloud provider belongs to.
     *
     * @var Team
     */
    private $team;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, Team $team)
    {
        parent::__construct($id, $name);

        $this->team = $team;
    }

    /**
     * Create a cloud provider from the given array.
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'team'])) {
            throw new InvalidArgumentException('Unable to create a cloud provider using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            Team::fromArray((array) $data['team'])
        );
    }

    /**
     * Get the team that the cloud provider belongs to.
     */
    public function getTeam(): Team
    {
        return $this->team;
    }
}
