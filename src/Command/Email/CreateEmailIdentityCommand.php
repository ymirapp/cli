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
use Symfony\Component\Console\Input\InputOption;

class CreateEmailIdentityCommand extends AbstractEmailIdentityCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'email:identity:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new email identity')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the email identity')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the email identity will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the email identity will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $name = $this->input->getStringArgument('name');

        if (empty($name)) {
            $name = $this->output->ask('What is the name of the email identity');
        }

        $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider where the email identity will be created');

        $identity = $this->apiClient->createEmailIdentity($providerId, $name, $this->determineRegion('Enter the name of the region where the email identity will be created', $providerId));

        $this->output->info('Email identity created');

        if ('domain' === $identity['type']) {
            $this->displayDkimAuthenticationRecords($identity->toArray());
        } elseif ('email' === $identity['type']) {
            $this->output->newLine();
            $this->output->important(sprintf('A verification email was sent to %s to validate the email identity', $identity['name']));
        }
    }
}
