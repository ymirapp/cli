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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Event subscriber that cleans up project folder when command terminates.
 */
class CleanupSubscriber implements EventSubscriberInterface
{
    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The hidden directory used by Ymir.
     *
     * @var string
     */
    private $hiddenDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, string $hiddenDirectory)
    {
        $this->filesystem = $filesystem;
        $this->hiddenDirectory = rtrim($hiddenDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    /**
     * Remove hidden directory when console terminates.
     */
    public function onConsoleTerminate(): void
    {
        $this->filesystem->remove($this->hiddenDirectory);
    }
}
