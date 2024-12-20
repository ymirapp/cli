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

class DeleteCertificateCommand extends AbstractCertificateCommand
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
            ->setDescription('Delete a SSL certificate')
            ->addArgument('certificate', InputArgument::REQUIRED, 'The ID of the SSL certificate to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $certificateId = $this->getCertificateArgument();

        if (!$this->output->confirm('Are you sure you want to delete this SSL certificate?', false)) {
            return;
        }

        $this->apiClient->deleteCertificate($certificateId);

        $this->output->info('SSL certificate deleted');
    }
}
