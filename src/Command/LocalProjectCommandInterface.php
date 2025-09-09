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

namespace Ymir\Cli\Command;

/**
 * Marker interface for commands that require a valid "ymir.yml" project configuration file.
 *
 * Commands implementing this interface trigger validation of the local project configuration
 * before execution via the LoadProjectConfigurationSubscriber.
 */
interface LocalProjectCommandInterface
{
}
