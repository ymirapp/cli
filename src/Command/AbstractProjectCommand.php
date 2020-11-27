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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ymir\Cli\Command\Project\InitializeProjectCommand;

/**
 * Base command for interacting with a project.
 */
abstract class AbstractProjectCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (InitializeProjectCommand::NAME !== $this->getName()) {
            $this->projectConfiguration->validate();
        }

        return parent::execute($input, $output);
    }
}
