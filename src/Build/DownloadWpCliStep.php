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

namespace Ymir\Cli\Build;

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class DownloadWpCliStep implements BuildStepInterface
{
    /**
     * The WP-CLI version to download.
     */
    private const VERSION = '2.12.0';

    /**
     * The path to the WP-CLI bin directory.
     *
     * @var string
     */
    private $binDirectory;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem)
    {
        $this->binDirectory = rtrim($buildDirectory, '/').'/bin';
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Downloading WP-CLI';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $wpCliPath = $this->binDirectory.'/wp';

        if (!$this->filesystem->exists($this->binDirectory)) {
            $this->filesystem->mkdir($this->binDirectory, 0755);
        }

        $this->filesystem->copy(sprintf('https://github.com/wp-cli/wp-cli/releases/download/v%1$s/wp-cli-%1$s.phar', self::VERSION), $wpCliPath, true);
        $this->filesystem->chmod($wpCliPath, 0755);
    }
}
