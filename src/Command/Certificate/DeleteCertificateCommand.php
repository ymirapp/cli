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

namespace Ymir\Cli\Command\Certificate;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Certificate;

class DeleteCertificateCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an SSL certificate')
            ->addArgument('certificate', InputArgument::OPTIONAL, 'The ID of the SSL certificate to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $certificate = $this->resolve(Certificate::class, 'Which SSL certificate would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the SSL certificate #<comment>%d</comment>?', $certificate->getId()), false)) {
            return;
        }

        $this->apiClient->deleteCertificate($certificate);

        $this->output->info('SSL certificate deleted');
    }
}
