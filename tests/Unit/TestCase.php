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

namespace Ymir\Cli\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * @coversNothing
 */
class TestCase extends BaseTestCase
{
    /**
     * The Faker instance.
     *
     * @var Generator
     */
    protected $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->faker = Factory::create();
    }
}
