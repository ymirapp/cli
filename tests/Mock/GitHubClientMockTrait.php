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
use Ymir\Cli\GitHubClient;

trait GitHubClientMockTrait
{
    /**
     * Get a mock of a GitHubClient object.
     */
    private function getGitHubClientMock(): MockObject
    {
        return $this->getMockBuilder(GitHubClient::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
