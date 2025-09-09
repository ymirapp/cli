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

namespace Ymir\Cli\Tests\Unit\Project\Build;

use Ymir\Cli\Project\Build\CompressBuildFilesStep;
use Ymir\Cli\Tests\TestCase;

class CompressBuildFilesStepTest extends TestCase
{
    public function testGetDescription(): void
    {
        $step = new CompressBuildFilesStep('artifact.zip', 'build');

        $this->assertSame('Compressing build files', $step->getDescription());
    }
}
