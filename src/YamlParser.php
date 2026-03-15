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

use Symfony\Component\Yaml\Yaml;
use Ymir\Cli\Exception\YamlParseException;

class YamlParser
{
    /**
     * Parse the given YAML file.
     */
    public function parse(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);

        if (false === $contents) {
            throw new YamlParseException(sprintf('Unable to read the YAML file at "%s"', $filePath));
        }

        try {
            $configuration = Yaml::parse($contents);
        } catch (\Throwable $exception) {
            throw new YamlParseException(sprintf('Error parsing YAML file at "%s": %s', $filePath, $exception->getMessage()));
        }

        if (null === $configuration) {
            return [];
        } elseif (!is_array($configuration)) {
            throw new YamlParseException(sprintf('Error parsing YAML file at "%s"', $filePath));
        }

        return $configuration;
    }
}
