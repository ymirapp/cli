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

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\OutputStyle;

class ListCertificatesCommand extends AbstractCertificateCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'certificate:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the SSL certificates that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $certificates = $this->apiClient->getCertificates($this->cliConfiguration->getActiveTeamId());

        $output->table(
            ['Id', 'Provider', 'Region', 'Domains', 'Status', 'In Use'],
            $certificates->map(function (array $certificate) {
                return [$certificate['id'], $certificate['provider']['name'], $certificate['region'], $this->getDomainsList($certificate), $certificate['status'], $certificate['in_use'] ? 'yes' : 'no'];
            })->all()
        );
    }
}
