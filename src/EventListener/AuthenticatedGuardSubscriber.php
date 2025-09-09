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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\LoginCommand;
use Ymir\Cli\Exception\RuntimeException;

class AuthenticatedGuardSubscriber implements EventSubscriberInterface
{
    /**
     * Commands that exempted from the guard check.
     */
    private const IGNORED_COMMANDS = [
        'help',
        'list',
        LoginCommand::NAME,
    ];

    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => ['onConsoleCommand', PHP_INT_MAX],
        ];
    }

    /**
     * Checks that the user is authenticated before running a command.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof Command || in_array($command->getName(), self::IGNORED_COMMANDS)) {
            return;
        }

        if (!$this->apiClient->isAuthenticated()) {
            throw new RuntimeException(sprintf('Please authenticate using the "%s" command before using this command', LoginCommand::NAME));
        }
    }
}
