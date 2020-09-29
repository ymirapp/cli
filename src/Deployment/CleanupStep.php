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

namespace Ymir\Cli\Deployment;

use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Console\OutputStyle;

class CleanupStep implements DeploymentStepInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The hidden directory used by Ymir.
     *
     * @var string
     */
    private $hiddenDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, string $hiddenDirectory)
    {
        $this->filesystem = $filesystem;
        $this->hiddenDirectory = rtrim($hiddenDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, OutputStyle $output)
    {
        $output->info('Cleaning up deployment files');

        $this->filesystem->remove($this->hiddenDirectory);
    }
}
