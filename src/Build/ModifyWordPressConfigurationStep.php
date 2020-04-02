<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Build;

use Placeholder\Cli\ProjectConfiguration;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class ModifyWordPressConfigurationStep implements BuildStepInterface
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
     * The project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem, ProjectConfiguration $projectConfiguration)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Modifying WordPress configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function perform()
    {
        $wpConfigFile = $this->buildDirectory.'/wp-config.php';

        if (!$this->filesystem->exists($wpConfigFile)) {
            throw new RuntimeException('No wp-config.php found in the build directory');
        }

        $wpConfig = file($wpConfigFile, FILE_IGNORE_NEW_LINES);

        if (!is_array($wpConfig)) {
            throw new RuntimeException('Unable to read wp-config.php');
        }

        $constants = ['WP_HOME', 'WP_SITEURL'];

        $wpConfig = array_map(function (string $line) use ($constants) {
            return preg_replace('/('.implode('|', $constants).')/i', 'NULL_\1', $line);
        }, $wpConfig);

        $wpConfig = array_map(function (string $line) {
            if (preg_match('/require_once\s+ABSPATH\s*\.\s*\'wp-settings.php\';/', $line)) {
                $line = "require_once ABSPATH.'placeholder-config.php'".PHP_EOL.$line;
            }

            return $line;
        }, $wpConfig);

        $this->filesystem->dumpFile($wpConfigFile, implode("\n", $wpConfig));
    }
}
