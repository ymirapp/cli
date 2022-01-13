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

namespace Ymir\Cli\Command\Project;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;

class ValidateProjectCommand extends AbstractProjectCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'validate';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:validate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Validates the project\'s ymir.yml file')
            ->setAliases([self::ALIAS])
            ->addArgument('environments', InputArgument::OPTIONAL, 'The names of the environments to validate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $environments = $input->getArgument('environments');

        if (!is_array($environments)) {
            $environments = (array) $environments;
        }

        $this->apiClient->validateProjectConfiguration($this->projectConfiguration, $environments);

        $output->info('Project <comment>ymir.yml</comment> file is valid');
    }
}
