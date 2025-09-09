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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Resource\Model\Environment;

class DownloadEnvironmentVariablesCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:download';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Filesystem $filesystem)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Download an environment\'s environment variables into an environment file')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to download environment variables from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to download environment variables from?');

        $filename = sprintf('.env.%s', $environment->getName());

        $this->filesystem->dumpFile($this->getProjectDirectory().'/'.$filename, $this->apiClient->getEnvironmentVariables($this->getProject(), $environment)->sortKeys()->map(function (string $value, string $key) {
            return sprintf('%s=%s', $key, $value);
        })->join(PHP_EOL));

        $this->output->infoWithValue('Environment variables downloaded to', $filename);
    }
}
