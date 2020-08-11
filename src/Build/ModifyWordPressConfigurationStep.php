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
use Ymir\Cli\ProjectConfiguration;

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
        return 'Modifying WordPress configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(string $environment, ProjectConfiguration $projectConfiguration)
    {
        $projectType = $projectConfiguration->getProjectType();

        if ('wordpress' === $projectType) {
            $this->modifyWordPressConfig((array) $projectConfiguration->getEnvironment($environment));
        } elseif ('bedrock' === $projectType) {
            $this->filesystem->remove($this->buildDirectory.'/.env');
        }
    }

    /**
     * Modify WordPress configuration to use environment variables.
     */
    private function modifyWordPressConfig(array $environment)
    {
        $wpConfigFile = $this->buildDirectory.'/wp-config.php';

        if (!$this->filesystem->exists($wpConfigFile)) {
            throw new RuntimeException('No wp-config.php found in the build directory');
        }

        $wpConfig = file($wpConfigFile, FILE_IGNORE_NEW_LINES);

        if (!is_array($wpConfig)) {
            throw new RuntimeException('Unable to read wp-config.php');
        }

        $constants = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
            'AUTOMATIC_UPDATER_DISABLED',
            'DISABLE_WP_CRON',
            'DISALLOW_FILE_EDIT',
            'DISALLOW_FILE_MODS',
            'DOMAIN_CURRENT_SITE',
            'WP_HOME',
            'WP_SITEURL',
        ];

        if (!empty($environment['database'])) {
            $constants = array_merge($constants, ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
        }

        $wpConfig = array_map(function (string $line) use ($constants) {
            return preg_replace('/('.implode('|', $constants).')/i', 'NULL_\1', $line);
        }, $wpConfig);

        $wpConfig = array_map(function (string $line) {
            if (preg_match('/require_once\s+ABSPATH\s*\.\s*\'wp-settings.php\';/', $line)) {
                $line = 'require_once ABSPATH.\'ymir-config.php\';'.PHP_EOL.$line;
            }

            return $line;
        }, $wpConfig);

        $this->filesystem->dumpFile($wpConfigFile, implode(PHP_EOL, $wpConfig));

        $configFile = 'ymir-config.php';
        $configStubPath = $this->stubDirectory.'/'.$configFile;

        if (!$this->filesystem->exists($configStubPath)) {
            throw new RuntimeException(sprintf('Cannot find "%s" stub file', $configFile));
        }

        $this->filesystem->copy($configStubPath, $this->buildDirectory.'/'.$configFile);
    }
}
