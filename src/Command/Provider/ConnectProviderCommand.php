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

namespace Ymir\Cli\Command\Provider;

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\ProjectConfiguration;

class ConnectProviderCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:connect';

    /**
     * The path to the user's home directory.
     *
     * @var string
     */
    private $homeDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, string $homeDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->homeDirectory = rtrim($homeDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Connect a cloud provider to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $name = $output->ask('Please enter a name for the cloud provider connection', 'AWS');

        $credentials = $this->getAwsCredentials($output);

        $this->apiClient->createProvider($name, $credentials, $this->cliConfiguration->getActiveTeamId());

        $output->info('Cloud provider connected');
    }

    /**
     * Get the AWS credentials.
     */
    private function getAwsCredentials(ConsoleOutput $output): array
    {
        $credentials = $this->getAwsCredentialsFromFile($output);

        return !empty($credentials) ? $credentials : [
            'key' => $output->ask('Please enter your AWS user key'),
            'secret' => $output->ask('Please enter your AWS user secret'),
        ];
    }

    /**
     * Get the AWS credentials from the credentials file.
     */
    private function getAwsCredentialsFromFile(ConsoleOutput $output): array
    {
        $credentialsFilePath = $this->homeDirectory.'/.aws/credentials';

        if (!is_file($credentialsFilePath)
            || !$output->confirm('Would you like to import credentials from your AWS credentials file?')
        ) {
            return [];
        }

        $parsedCredentials = collect(parse_ini_file($credentialsFilePath, true));

        if (empty($parsedCredentials)) {
            return [];
        }

        $credentials = $output->choice(
            'Enter the name of the credentials to import from your AWS credentials file',
            $parsedCredentials->mapWithKeys(function ($credentials, $key) {
                return [$key => $key];
            })->all()
        );

        return [
            'key' => $parsedCredentials[$credentials]['aws_access_key_id'],
            'secret' => $parsedCredentials[$credentials]['aws_secret_access_key'],
        ];
    }
}
