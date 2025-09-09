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

final class Secret extends AbstractResourceModel
{
    /**
     * The date and time when the secret was last updated.
     *
     * @var string
     */
    private $updatedAt;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $updatedAt)
    {
        parent::__construct($id, $name);

        $this->updatedAt = $updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'updated_at'])) {
            throw new InvalidArgumentException('Unable to create a secret using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['updated_at']
        );
    }

    /**
     * Get the date and time when the secret was last updated.
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
