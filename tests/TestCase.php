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

namespace Ymir\Cli\Tests;

use Faker\Factory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class TestCase extends MockeryTestCase
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
        parent::setUp();

        $this->faker = Factory::create();
    }
}
