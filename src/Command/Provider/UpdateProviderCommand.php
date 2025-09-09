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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Requirement\AwsCredentialsRequirement;
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
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Update a cloud provider')
            ->addArgument('provider', InputArgument::OPTIONAL, 'The ID or name of the cloud provider to update')
            ->addArgument('name', InputArgument::OPTIONAL, 'The new name of the cloud provider connection');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to update?');

        $name = $this->fulfill(new NameRequirement('What is the name of the cloud provider connection?', $provider->getName()));
        $credentials = $this->fulfill(new AwsCredentialsRequirement());

        $this->apiClient->updateProvider($provider, $credentials, $name);

        $this->output->info('Cloud provider updated');
    }
}
