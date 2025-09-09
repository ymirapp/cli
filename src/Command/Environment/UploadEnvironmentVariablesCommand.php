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
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Resource\Model\Environment;

class UploadEnvironmentVariablesCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:variables:upload';

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
            ->setDescription('Upload the environment variables in an environment file to an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to upload environment variables to');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to upload environment variables to?');

        $fileName = sprintf('.env.%s', $environment->getName());
        $filePath = $this->getProjectDirectory().'/'.$fileName;

        if (!$this->filesystem->exists($filePath)) {
            throw new ResourceStateException(sprintf('No environment file found for the "%s" environment, but you can download it using the "%s" command', $environment->getName(), DownloadEnvironmentVariablesCommand::NAME));
        } elseif (!$this->output->confirm('Uploading the environment file will overwrite all environment variables. Are you sure you want to proceed?', false)) {
            return;
        }

        $this->apiClient->changeEnvironmentVariables($this->getProject(), $environment, collect(explode(PHP_EOL, (string) file_get_contents($filePath)))->mapWithKeys(function (string $line) {
            $matches = [];
            preg_match('/([^=]*)=(.*)/', $line, $matches);

            return isset($matches[1], $matches[2]) ? [$matches[1] => $matches[2]] : [];
        })->all(), true);

        $this->output->infoWithRedeployWarning('Environment variables uploaded', $environment->getName());

        if ($this->output->confirm(sprintf('Do you want to delete the "<comment>%s</comment>" environment file?', $fileName))) {
            $this->filesystem->remove($filePath);
        }
    }
}
