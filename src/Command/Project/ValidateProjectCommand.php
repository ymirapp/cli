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
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

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
     * The project Dockerfile.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Dockerfile $dockerfile, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->dockerfile = $dockerfile;
    }

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
    protected function perform()
    {
        $environments = (array) $this->input->getArgument('environments');
        $environments = $this->projectConfiguration->getEnvironments()->filter(function (array $configuration, string $environment) use ($environments) {
            return empty($environments) || in_array($environment, $environments);
        });

        $environments->filter(function (array $configuration) {
            return 'image' === Arr::get($configuration, 'deployment');
        })->each(function (array $configuration, string $environment) {
            $this->dockerfile->validate($environment, Arr::get($configuration, 'architecture', ''));
        });

        $message = 'Project <comment>ymir.yml</comment> file is valid';
        $response = $this->apiClient->validateProjectConfiguration($this->projectConfiguration, $environments->keys()->all());
        $valid = $response->every(function (array $environment) {
            return empty($environment['warnings']);
        });

        if (!$valid) {
            $message .= ' with the following warnings:';
        }

        $this->output->info($message);

        if (!$valid) {
            $this->output->table(['Environment', 'Warning'], $response->filter(function (array $environment) {
                return !empty($environment['warnings']);
            })->flatMap(function (array $environment, string $name) {
                return collect($environment['warnings'])->map(function (string $warning) use ($name) {
                    return [$name, $warning];
                })->all();
            })->all());
        }
    }
}
