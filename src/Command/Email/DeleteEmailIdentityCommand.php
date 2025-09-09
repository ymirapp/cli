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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\EmailIdentity;

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
            ->setDescription('Delete an email identity')
            ->addArgument('identity', InputArgument::OPTIONAL, 'The ID or name of the email identity to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $identity = $this->resolve(EmailIdentity::class, 'Which email identity would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" email identity?', $identity->getName()), false)) {
            return;
        }

        $this->apiClient->deleteEmailIdentity($identity);

        $this->output->info('Email identity deleted');
    }
}
