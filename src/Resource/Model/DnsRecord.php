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

final class DnsRecord extends AbstractResourceModel
{
    /**
     * Whether the DNS record is internal or not.
     *
     * @var bool
     */
    private $internal;

    /**
     * The type of the DNS record.
     *
     * @var string
     */
    private $type;

    /**
     * The value of the DNS record.
     *
     * @var string
     */
    private $value;

    /**
     * Constructor.
     */
    public function __construct(int $id, string $name, string $type, string $value, bool $internal = false)
    {
        parent::__construct($id, $name);

        $this->internal = $internal;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): self
    {
        if (!Arr::has($data, ['id', 'name', 'type', 'value'])) {
            throw new InvalidArgumentException('Unable to create a DNS record using the given array data');
        }

        return new self(
            (int) $data['id'],
            (string) $data['name'],
            (string) $data['type'],
            (string) $data['value'],
            (bool) Arr::get($data, 'internal', false)
        );
    }

    /**
     * Get the type of the DNS record.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the value of the DNS record.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Whether the DNS record is internal or not.
     */
    public function isInternal(): bool
    {
        return $this->internal;
    }
}
