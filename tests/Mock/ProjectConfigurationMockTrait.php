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
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

trait ProjectConfigurationMockTrait
{
    /**
     * Get a mock of a ProjectConfiguration object.
     */
    private function getProjectConfigurationMock(): MockObject
    {
        return $this->getMockBuilder(ProjectConfiguration::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
