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

namespace Ymir\Cli\Console;

use Symfony\Component\Console\Input\InputInterface as SymfonyInputInterface;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\NonInteractiveRequiredArgumentException;
use Ymir\Cli\Exception\NonInteractiveRequiredOptionException;

class Input
{
    /**
     * Symfony console input.
     *
     * @var SymfonyInputInterface
     */
    private $input;

    /**
     * Constructor.
     */
    public function __construct(SymfonyInputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * Get the argument value for the given name.
     */
    public function getArgument(string $name)
    {
        return $this->input->getArgument($name);
    }

    /**
     * Get the value of an argument that should be an array.
     */
    public function getArrayArgument(string $argument, bool $requiredNonInteractive = true): array
    {
        $value = $this->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredArgumentException($argument);
        } elseif (null !== $value && !is_array($value)) {
            throw new InvalidInputException(sprintf('The "%s" argument must be an array value', $argument));
        }

        return (array) $value;
    }

    /**
     * Get the value of a option that should be an array.
     */
    public function getArrayOption(string $option, bool $requiredNonInteractive = false): ?array
    {
        $value = null;

        if ($this->hasOption($option)) {
            $value = $this->getOption($option);
        }

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredOptionException($option);
        } elseif (null !== $value && !is_array($value)) {
            throw new InvalidInputException(sprintf('The "--%s" option must be an array', $option));
        }

        return $value;
    }

    /**
     * Get the value of an option that should be boolean.
     */
    public function getBooleanOption(string $option): bool
    {
        return $this->hasOption($option) && $this->getOption($option);
    }

    /**
     * Get the value of an argument that should be numeric.
     */
    public function getNumericArgument(string $argument, bool $requiredNonInteractive = true): int
    {
        $value = $this->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredArgumentException($argument);
        } elseif (null !== $value && !is_numeric($value)) {
            throw new InvalidInputException(sprintf('The "%s" argument must be a numeric value', $argument));
        }

        return (int) $value;
    }

    /**
     * Get the value of a option that should be numeric. Returns null if not present.
     */
    public function getNumericOption(string $option, bool $requiredNonInteractive = false): ?int
    {
        $value = null;

        if ($this->hasOption($option)) {
            $value = $this->getOption($option);
        }

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredOptionException($option);
        } elseif (null !== $value && !is_numeric($value)) {
            throw new InvalidInputException(sprintf('The "--%s" option must be a numeric value', $option));
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Get the option value for the given name.
     */
    public function getOption(string $name)
    {
        return $this->input->getOption($name);
    }

    /**
     * Get the value of an argument that should be a string.
     */
    public function getStringArgument(string $argument, bool $requiredNonInteractive = true): string
    {
        $value = $this->getArgument($argument);

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredArgumentException($argument);
        } elseif (null !== $value && !is_string($value)) {
            throw new InvalidInputException(sprintf('The "%s" argument must be a string value', $argument));
        }

        return (string) $value;
    }

    /**
     * Get the value of a option that should be a string. Returns null if not present.
     */
    public function getStringOption(string $option, bool $requiredNonInteractive = false): ?string
    {
        $value = null;

        if ($this->hasOption($option)) {
            $value = $this->getOption($option);
        }

        if (null === $value && $requiredNonInteractive && !$this->isInteractive()) {
            throw new NonInteractiveRequiredOptionException($option);
        } elseif (null !== $value && !is_string($value)) {
            throw new InvalidInputException(sprintf('The "--%s" option must be a string value', $option));
        }

        return $value;
    }

    /**
     * Check if the input has an argument with the given name.
     */
    public function hasArgument(string $name): bool
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Check if the input has an option with the given name.
     */
    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    /**
     * Check if the input is in interactive mode.
     */
    public function isInteractive(): bool
    {
        return $this->input->isInteractive();
    }
}
