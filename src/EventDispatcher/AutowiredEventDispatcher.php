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

namespace Ymir\Cli\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;

class AutowiredEventDispatcher extends EventDispatcher
{
    /**
     * Constructor.
     */
    public function __construct(iterable $eventSubscribers = [])
    {
        parent::__construct();

        foreach ($eventSubscribers as $eventSubscriber) {
            $this->addSubscriber($eventSubscriber);
        }
    }
}
