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

namespace Ymir\Cli;

use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Collection;

class CliConfiguration
{
    /**
     * The path to the configuration file.
     *
     * @var string
     */
    private $configurationFilePath;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The configuration options.
     *
     * @var Collection
     */
    private $options;

    /**
     * Constructor.
     */
    public function __construct(string $configurationFilePath, Filesystem $filesystem)
    {
        $this->configurationFilePath = $configurationFilePath;
        $this->filesystem = $filesystem;
        $this->options = $this->load($configurationFilePath);
    }

    /**
     * Save the options back to the configuration file when we're destroying the object.
     */
    public function __destruct()
    {
        $this->filesystem->dumpFile($this->configurationFilePath, (string) json_encode($this->options, JSON_PRETTY_PRINT));
    }

    /**
     * Get the given configuration option or return the default.
     */
    public function get(string $option, $default = null)
    {
        return $this->options->get($option, $default);
    }

    /**
     * Checks if the configuration has the given option.
     */
    public function has(string $option): bool
    {
        return $this->options->has($option);
    }

    /**
     * Set the configuration option.
     */
    public function set(string $option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Load the options from the configuration file.
     */
    private function load(string $configurationFilePath): Collection
    {
        $configuration = [];

        if ($this->filesystem->exists($configurationFilePath)) {
            $configuration = json_decode((string) file_get_contents($configurationFilePath), true);
        }

        return collect($configuration);
    }
}
