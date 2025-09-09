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

final class User extends AbstractResourceModel
{
    /**
     * The email of the user.
     *
     * @var string
     */
    private $email;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $email = '')
    {
        parent::__construct($id, $name);

        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name'])) {
            throw new InvalidArgumentException('Unable to create a user using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) Arr::get($data, 'email', '')
        );
    }

    /**
     * Get the email of the user.
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
