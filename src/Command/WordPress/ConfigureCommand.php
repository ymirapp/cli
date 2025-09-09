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

namespace Ymir\Cli\Command\WordPress;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Configuration\WordPress\WordPressConfigurationChangeInterface;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Support\Arr;

class ConfigureCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'wordpress:configure';

    /**
     * Collection of all the WordPress configuration changes.
     *
     * @var Collection
     */
    private $configurationChanges;

    /**
     * The WP-CLI executable.
     *
     * @var WpCliExecutable
     */
    private $wpCliExecutable;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, WpCliExecutable $wpCliExecutable, iterable $configurationChanges = [])
    {
        parent::__construct($apiClient, $contextFactory);

        $this->configurationChanges = new Collection();
        $this->wpCliExecutable = $wpCliExecutable;

        foreach ($configurationChanges as $configurationChange) {
            $this->addConfigurationChange($configurationChange);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Configure a WordPress project by scanning your installed plugins and themes')
            ->addArgument('environments', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The names of the environments to configure');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (!$this->getProjectConfiguration()->getProjectType() instanceof AbstractWordPressProjectType) {
            throw new UnsupportedProjectException('You can only use this command with WordPress, Bedrock or Radicle projects');
        } elseif (!$this->wpCliExecutable->isInstalled()) {
            throw new SystemException('WP-CLI must be available globally to use this command');
        } elseif (!$this->wpCliExecutable->isWordPressInstalled()) {
            throw new SystemException('WordPress must be installed and connected to a database to use this command');
        }

        $requestedEnvironments = collect($this->input->getArrayArgument('environments', false));

        $projectEnvironments = $this->getProjectConfiguration()->getEnvironments();
        $missingEnvironments = $requestedEnvironments->diff($projectEnvironments->keys());

        if ($missingEnvironments->isNotEmpty()) {
            throw new InvalidInputException(sprintf('Environment "%s" not found in ymir.yml file', $missingEnvironments->first()));
        }

        $environments = $projectEnvironments->when($requestedEnvironments->isNotEmpty(), function (Collection $environments) use ($requestedEnvironments) {
            return $environments->only($requestedEnvironments);
        });

        $this->output->info('Scanning your project');

        $plugins = $this->wpCliExecutable->listPlugins()->groupBy('status');

        $activePlugins = $plugins->only(['active', 'must-use'])->flatten(1);
        $inactivePlugins = $plugins->only(['inactive'])->flatten(1);

        $this->applyConfigurationChanges('The following plugin(s) are <comment>active</comment> and have available configuration changes:', $activePlugins, $environments);
        $this->applyConfigurationChanges('The following plugin(s) are <comment>inactive</comment> and have available configuration changes:', $inactivePlugins, $environments, false);

        $this->output->info('Project configuration updated successfully');
    }

    /**
     * Add a configuration change to the command.
     */
    private function addConfigurationChange(WordPressConfigurationChangeInterface $configurationChange): void
    {
        $this->configurationChanges->push($configurationChange);
    }

    /**
     * Apply the given configuration changes to the given environments.
     */
    private function applyConfigurationChanges(string $message, Collection $plugins, Collection $environments, bool $apply = true): void
    {
        $appliedConfigurationChanges = $this->configurationChanges->filter(function (WordPressConfigurationChangeInterface $configurationChange) use ($plugins): bool {
            return $plugins->contains('name', $configurationChange->getName());
        });

        if ($appliedConfigurationChanges->isEmpty()) {
            return;
        }

        $this->output->info($message);

        $this->output->list($appliedConfigurationChanges->map(function (WordPressConfigurationChangeInterface $configurationChange) use ($plugins) {
            $name = $configurationChange->getName();

            return Arr::get($plugins->firstWhere('name', $name), 'title', $name);
        }));

        if (!$this->output->confirm('Do you want to apply them?', $apply)) {
            return;
        }

        $appliedConfigurationChanges->each(function (WordPressConfigurationChangeInterface $configurationChange) use ($environments): void {
            $environments->keys()->each(function (string $environment) use ($configurationChange): void {
                $this->getProjectConfiguration()->applyChangesToEnvironment($environment, $configurationChange);
            });
        });
    }
}
