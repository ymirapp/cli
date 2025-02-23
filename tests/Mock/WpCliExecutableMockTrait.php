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

namespace Ymir\Cli\Tests\Mock;

use PHPUnit\Framework\MockObject\MockObject;
use Ymir\Cli\Executable\WpCliExecutable;

trait WpCliExecutableMockTrait
{
    /**
     * Get a mock of a WpCliExecutable object.
     */
    private function getWpCliExecutableMock(): MockObject
    {
        return $this->getMockBuilder(WpCliExecutable::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
