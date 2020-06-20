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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('certificate', InputArgument::REQUIRED, 'The ID of the SSL certificate to delete')
            ->setDescription('Delete an existing SSL certificate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $certificateId = $input->getArgument('certificate');

        if (null === $certificateId || is_array($certificateId) || !is_numeric($certificateId)) {
            throw new RuntimeException('The "certificate" argument must be the ID of the SSL certificate');
        } elseif ($input->isInteractive() && !$output->confirm('Are you sure you want to delete this SSL certificate?', false)) {
            return;
        }

        $this->apiClient->deleteCertificate((int) $certificateId);

        $output->info('SSL certificate deleted');
    }
}
