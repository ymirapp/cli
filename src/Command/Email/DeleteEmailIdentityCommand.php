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

namespace Ymir\Cli\Command\Email;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class DeleteEmailIdentityCommand extends AbstractEmailIdentityCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'email:identity:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an email identity')
            ->addArgument('identity', InputArgument::OPTIONAL, 'The ID or name of the email identity to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $identity = $this->determineEmailIdentity('Which email identity would you like to delete', $input, $output);

        if (!$output->confirm('Are you sure you want to delete this email identity?', false)) {
            return;
        }

        $this->apiClient->deleteEmailIdentity((int) $identity['id']);

        $output->info('Email identity deleted');
    }
}
