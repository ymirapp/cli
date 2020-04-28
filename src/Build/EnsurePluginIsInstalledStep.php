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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class EnsurePluginIsInstalledStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The directory where the stub files are.
     *
     * @var string
     */
    private $stubDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem, string $stubDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->stubDirectory = rtrim($stubDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Ensuring Ymir plugin is installed';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment)
    {
        $process = Process::fromShellCommandline(sprintf('%s plugin list --fields=file --format=json', rtrim($this->buildDirectory, '/').'/bin/wp'));
        $process->run();

        $plugins = collect(json_decode($process->getOutput()));

        if ($plugins->isEmpty()) {
            throw new RuntimeException('Unable to get the list of installed plugins');
        }

        if (!$plugins->contains(function (\stdClass $plugin) {
            return !empty($plugin->file) && preg_match('/ymir\.php$/', $plugin->file);
        })) {
            throw new RuntimeException('Ymir plugin not found');
        }

        $mupluginStub = 'activate-ymir-plugin.php';
        $mupluginStubPath = $this->stubDirectory.'/'.$mupluginStub;

        if (!$this->filesystem->exists($mupluginStubPath)) {
            throw new RuntimeException(sprintf('Cannot find "%s" stub file', $mupluginStub));
        }

        $mupluginsDirectory = $this->buildDirectory.'/wp-content/mu-plugins';

        if (!$this->filesystem->exists($mupluginsDirectory)) {
            $this->filesystem->mkdir($mupluginsDirectory);
        }

        $this->filesystem->copy($mupluginStubPath, $mupluginsDirectory.'/'.$mupluginStub);
    }
}
