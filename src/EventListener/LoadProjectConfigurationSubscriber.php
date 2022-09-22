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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class LoadProjectConfigurationSubscriber implements EventSubscriberInterface
{
    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ProjectConfiguration $projectConfiguration)
    {
        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }

    /**
     * Load the Ymir project configuration.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $configurationFilePath = $event->getInput()->getOption('ymir-file');

        if (!is_string($configurationFilePath)) {
            throw new RuntimeException('The "--ymir-file" option must be a string value');
        }

        $this->projectConfiguration->loadConfiguration($configurationFilePath);
    }
}
