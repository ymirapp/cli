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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Executable\ComposerExecutable;
use Ymir\Cli\Tests\TestCase;

class ComposerExecutableTest extends TestCase
{
    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $tempDirectory = sys_get_temp_dir().'/'.uniqid('ymir-test');

        (new Filesystem())->mkdir($tempDirectory);

        $this->tempDirectory = (string) realpath($tempDirectory);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDirectory);

        parent::tearDown();
    }

    public function testRequiresPackageReturnsFalseIfComposerJsonIsInvalid(): void
    {
        file_put_contents($this->tempDirectory.'/composer.json', '{');

        $this->assertFalse((new ComposerExecutable())->requiresPackage('ymirapp/laravel-bridge', $this->tempDirectory));
    }

    public function testRequiresPackageReturnsFalseIfComposerJsonIsMissing(): void
    {
        $this->assertFalse((new ComposerExecutable())->requiresPackage('ymirapp/laravel-bridge', $this->tempDirectory));
    }

    public function testRequiresPackageReturnsFalseIfPackageIsNotAProductionDependency(): void
    {
        file_put_contents($this->tempDirectory.'/composer.json', json_encode([
            'require' => [
                'laravel/framework' => '^12.0',
            ],
        ]));

        $this->assertFalse((new ComposerExecutable())->requiresPackage('ymirapp/laravel-bridge', $this->tempDirectory));
    }

    public function testRequiresPackageReturnsFalseIfPackageIsOnlyADevelopmentDependency(): void
    {
        file_put_contents($this->tempDirectory.'/composer.json', json_encode([
            'require-dev' => [
                'ymirapp/laravel-bridge' => '^1.0',
            ],
        ]));

        $this->assertFalse((new ComposerExecutable())->requiresPackage('ymirapp/laravel-bridge', $this->tempDirectory));
    }

    public function testRequiresPackageReturnsTrueIfPackageIsAProductionDependency(): void
    {
        file_put_contents($this->tempDirectory.'/composer.json', json_encode([
            'require' => [
                'ymirapp/laravel-bridge' => '^1.0',
            ],
        ]));

        $this->assertTrue((new ComposerExecutable())->requiresPackage('ymirapp/laravel-bridge', $this->tempDirectory));
    }
}
