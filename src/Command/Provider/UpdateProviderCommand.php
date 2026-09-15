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

namespace Ymir\Cli\Command\Provider;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Requirement\CloudProviderAuthenticationMethodRequirement;
use Ymir\Cli\Resource\Requirement\CloudProviderCredentialsRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;

class UpdateProviderCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:update';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Update a cloud provider')
            ->addArgument('provider', InputArgument::OPTIONAL, 'The ID or name of the cloud provider to update')
            ->addArgument('name', InputArgument::OPTIONAL, 'The new name of the cloud provider connection')
            ->addOption('aws-profile', null, InputOption::VALUE_REQUIRED, 'The AWS credential profile used for access key authentication')
            ->addOption('role-arn', null, InputOption::VALUE_REQUIRED, 'The AWS IAM role ARN used for authentication');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(): void
    {
        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to update?');

        $name = $this->fulfill(new NameRequirement('What is the name of the cloud provider connection?', $provider->getName()));

        if ($provider->getName() === $name) {
            $name = null;
        }

        $credentials = null;

        if (null !== $this->input->getStringOption('aws-profile') || null !== $this->input->getStringOption('role-arn') || $this->output->confirm('Would you like to update the authentication method or credentials?', false)) {
            $provider = $this->apiClient->getProvider($provider->getId());
            $credentials = $this->fulfill(new CloudProviderCredentialsRequirement($provider), [
                'authentication_method' => $this->fulfill(new CloudProviderAuthenticationMethodRequirement('Which authentication method would you like to use?', $provider->getAuthenticationMethod())),
            ]);
        }

        if (null === $name && null === $credentials) {
            $this->output->comment('No changes made');

            return;
        }

        $this->apiClient->updateProvider($provider, $credentials, $name);

        $this->output->info('Cloud provider updated');
    }
}
