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

use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Resource\Model\Project;

class ValidateProjectCommand extends AbstractCommand implements LocalProjectCommandInterface
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

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Dockerfile $dockerfile)
    {
        parent::__construct($apiClient, $contextFactory);

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
            ->addArgument('environments', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The names of the environments to validate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $requestedEnvironments = collect($this->input->getArrayArgument('environments'));

        $projectEnvironments = $this->getProjectConfiguration()->getEnvironments();
        $missingEnvironments = $requestedEnvironments->diff($projectEnvironments->keys());

        if ($missingEnvironments->isNotEmpty()) {
            throw new InvalidInputException(sprintf('Environment "%s" not found in ymir.yml file', $missingEnvironments->first()));
        }

        $environmentsToValidate = $projectEnvironments->when($requestedEnvironments->isNotEmpty(), function (Collection $environments) use ($requestedEnvironments) {
            return $environments->only($requestedEnvironments);
        });

        $environmentsToValidate->filter(function (EnvironmentConfiguration $configuration): bool {
            return $configuration->isImageDeploymentType();
        })->each(function (EnvironmentConfiguration $configuration, string $environment): void {
            $this->dockerfile->validate($environment, $configuration->getArchitecture());
        });

        $message = 'Project <comment>ymir.yml</comment> file is valid';
        $response = $this->apiClient->validateProjectConfiguration($this->getProject(), $this->getProjectConfiguration()->toArray(), $environmentsToValidate->keys()->all());
        $valid = $response->every(function (array $environment) {
            return empty($environment['warnings']);
        });

        if (!$valid) {
            $message .= ' with the following warnings:';
        }

        $this->output->info($message);

        if (!$valid) {
            $this->output->table(['Environment', 'Warning'], $response->filter(function (array $environment): bool {
                return !empty($environment['warnings']);
            })->flatMap(function (array $environment, string $name) {
                return collect($environment['warnings'])->map(function (string $warning) use ($name) {
                    return [$name, $warning];
                })->all();
            })->all());
        }
    }
}
