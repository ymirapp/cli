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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class DeleteEmailIdentityCommand extends AbstractCommand
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
            ->addArgument('identity', InputArgument::REQUIRED, 'The ID or name of the email identity to delete')
            ->setDescription('Delete an existing email identity');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $identity = $this->apiClient->getEmailIdentity($this->getStringArgument($input, 'identity'));

        if ($input->isInteractive() && !$output->confirm('Are you sure you want to delete this email identity?', false)) {
            return;
        }

        $this->apiClient->deleteEmailIdentity((int) $identity['id']);

        $output->info('Email identity deleted');
    }
}
