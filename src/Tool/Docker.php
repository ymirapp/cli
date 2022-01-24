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

namespace Ymir\Cli\Tool;

class Docker extends CommandLineTool
{
    /**
     * Build a docker image.
     */
    public static function build(string $file, string $tag, ?string $cwd = null)
    {
        self::runCommand(sprintf('build --pull --file=%s --tag=%s .', $file, $tag), $cwd);
    }

    /**
     * Login to a Docker registry.
     */
    public static function login(string $username, string $password, string $server, ?string $cwd = null)
    {
        self::runCommand(sprintf('login --username %s --password %s %s', $username, $password, $server), $cwd);
    }

    /**
     * Push a docker image.
     */
    public static function push(string $image, ?string $cwd = null)
    {
        self::runCommand(sprintf('push %s', $image), $cwd);
    }

    /**
     * Create a docker image tag.
     */
    public static function tag(string $sourceImage, string $targetImage, ?string $cwd = null)
    {
        self::runCommand(sprintf('tag %s %s', $sourceImage, $targetImage), $cwd);
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommand(): string
    {
        return 'docker';
    }

    /**
     * {@inheritdoc}
     */
    protected static function getName(): string
    {
        return 'Docker';
    }
}
