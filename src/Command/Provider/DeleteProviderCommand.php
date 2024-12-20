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

class DeleteProviderCommand extends AbstractProviderCommand
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
            ->addArgument('provider', InputArgument::REQUIRED, 'The ID of the cloud provider to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (!$this->output->confirm('Are you sure you want to delete this cloud provider? All resources associated to it will also be deleted on Ymir. They won\'t be deleted on your cloud provider.', false)) {
            return;
        }

        $this->apiClient->deleteProvider($this->input->getNumericArgument('provider'));

        $this->output->info('Cloud provider deleted');
    }
}
