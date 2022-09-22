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

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\Exception\CommandCancelledException;

class Application extends BaseApplication
{
    /**
     * Constructor.
     */
    public function __construct(iterable $commands, string $version)
    {
        parent::__construct('Ymir', $version);

        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderThrowable(\Throwable $exception, OutputInterface $output): void
    {
        if ($exception instanceof CommandCancelledException) {
            return;
        }

        parent::renderThrowable($exception, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption('ymir-file', null, InputOption::VALUE_OPTIONAL, 'Path to Ymir project configuration file', 'ymir.yml'));

        return $definition;
    }
}
