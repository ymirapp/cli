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

namespace Ymir\Cli\ProjectConfiguration\WordPress;

use Ymir\Cli\ProjectConfiguration\ConfigurationChangeInterface;

interface WordPressConfigurationChangeInterface extends ConfigurationChangeInterface
{
    /**
     * Get the name of the plugin or theme that this configuration change applies to.
     */
    public function getName(): string;
}
