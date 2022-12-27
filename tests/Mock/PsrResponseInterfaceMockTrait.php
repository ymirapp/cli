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
use Psr\Http\Message\ResponseInterface;

trait PsrResponseInterfaceMockTrait
{
    /**
     * Get a mock of a ResponseInterface object.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject&\Psr\Http\Message\ResponseInterface
     */
    private function getResponseInterfaceMock(): MockObject
    {
        return $this->getMockBuilder(ResponseInterface::class)
                    ->getMock();
    }
}
