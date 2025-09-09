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

namespace Ymir\Cli\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\RuntimeException;

class InteractivityGuardSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', 10],
        ];
    }

    /**
     * Checks that the command is run in interactive mode if required.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command instanceof AbstractCommand && $command->mustBeInteractive() && !$event->getInput()->isInteractive()) {
            throw new RuntimeException(sprintf('Cannot run "%s" command in non-interactive mode', $command->getName()));
        }
    }
}
