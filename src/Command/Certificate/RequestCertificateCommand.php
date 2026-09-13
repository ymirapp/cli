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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\Certificate;

class RequestCertificateCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:request';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Request a new SSL certificate')
            ->addArgument('domains', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'List of domains that the SSL certificate is for')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The ID of the cloud provider where the certificate will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the certificate will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(): void
    {
        $this->provision(Certificate::class);
    }
}
