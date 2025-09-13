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
use Symfony\Component\Console\Output\OutputInterface;

trait OutputInterfaceMockTrait
{
    /**
     * Get a mock of a OutputInterface object.
     */
    private function getOutputInterfaceMock(): MockObject
    {
        return $this->getMockBuilder(OutputInterface::class)
                    ->getMock();
    }
}
