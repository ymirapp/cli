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
use Ymir\Cli\WpCli;

class EnsurePluginIsInstalledStep implements BuildStepInterface
{
    /**
     * The bin directory where the WP-CLI was installed.
     *
     * @var string
     */
    private $binDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory)
    {
        $this->binDirectory = rtrim($buildDirectory, '/').'/bin';
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
        if (!WpCli::isPluginInstalled('ymir', $this->binDirectory, 'wp')) {
            throw new RuntimeException('Ymir plugin not found');
        }
    }
}
