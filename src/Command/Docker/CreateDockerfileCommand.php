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

namespace Ymir\Cli\Command\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class CreateDockerfileCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:create';

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
            ->setDescription('Create a new Dockerfile')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to create the Dockerfile for')
            ->addOption('configure-project', null, InputOption::VALUE_NONE, 'Configure project\'s ymir.yml file');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->input->getStringArgument('environment', false);

        if (!empty($environment) && !$this->getProjectConfiguration()->hasEnvironment($environment)) {
            throw new InvalidInputException(sprintf('Environment "%s" not found in ymir.yml file', $environment));
        } elseif (empty($environment) && $this->output->confirm('Would you like to create a Dockerfile for a specific environment?', false)) {
            $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to create a Dockerfile for?')->getName();
        }

        $message = 'Dockerfile created';

        if (!empty($environment)) {
            $message .= sprintf(' for "<comment>%s</comment>" environment', $environment);
        }

        if (!$this->dockerfile->exists($environment) || $this->output->confirm('Dockerfile already exists. Do you want to overwrite it?', false)) {
            $this->dockerfile->create($environment);

            $this->output->info($message);
        }

        if (!$this->input->getBooleanOption('configure-project') && !$this->output->confirm('Would you also like to configure your project for container image deployment?')) {
            return;
        }

        $configurationChange = new ImageDeploymentConfigurationChange();

        if (empty($environment)) {
            $this->getProjectConfiguration()->applyChangesToEnvironments($configurationChange);

            return;
        }

        $this->getProjectConfiguration()->applyChangesToEnvironment($environment, $configurationChange);
    }
}
