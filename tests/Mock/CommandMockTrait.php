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
use Symfony\Component\Console\Command\Command;

trait CommandMockTrait
{
    /**
     * Get a mock of a Command object.
     */
    private function getCommandMock(): MockObject
    {
        return $this->getMockBuilder(Command::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
