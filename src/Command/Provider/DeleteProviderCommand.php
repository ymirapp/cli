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

class DeleteProviderCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a cloud provider')
            ->addArgument('provider', InputArgument::OPTIONAL, 'The ID or name of the cloud provider to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "%s" cloud provider? All resources associated to it will also be deleted on Ymir. They won\'t be deleted on your cloud provider.', $provider->getName()), false)) {
            return;
        }

        $this->apiClient->deleteProvider($provider);

        $this->output->info('Cloud provider deleted');
    }
}
