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

namespace Ymir\Cli\Exception;

use Symfony\Component\Console\Exception\InvalidArgumentException as SymfonyInvalidArgumentException;

class InvalidArgumentException extends SymfonyInvalidArgumentException implements ExceptionInterface
{
}
