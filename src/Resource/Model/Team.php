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

final class Team extends AbstractResourceModel
{
    /**
     * The owner of the team.
     *
     * @var User
     */
    private $owner;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, User $owner)
    {
        parent::__construct($id, $name);

        $this->owner = $owner;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'owner'])) {
            throw new InvalidArgumentException('Unable to create a team using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            User::fromArray((array) $data['owner'])
        );
    }

    /**
     * Get the owner of the team.
     */
    public function getOwner(): User
    {
        return $this->owner;
    }
}
