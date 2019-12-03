<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Command\Provider;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\Command\AbstractCommand;
use Placeholder\Cli\Configuration;
use Placeholder\Cli\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;

class ConnectAwsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:connect';

    /**
     * The path to the AWS credentials file.
     *
     * @var string
     */
    private $credentialsFilePath;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, Configuration $configuration, string $homeDirectory)
    {
        parent::__construct($apiClient, $configuration);

        $credentialsFilePath = rtrim($homeDirectory, '/').'/.aws/credentials';

        if (file_exists($credentialsFilePath)) {
            $this->credentialsFilePath = $credentialsFilePath;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Connect an AWS account to the team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $name = $output->ask('Please enter a name for the AWS account connection');
        $credentials = $this->getCredentials($output);

        $this->apiClient->createProvider($name, $credentials, $this->getActiveTeamId());

        $output->writeln('AWS account connected successfully');
    }

    /**
     * Get the AWS credentials.
     */
    private function getCredentials(OutputStyle $output): array
    {
        $credentials = $this->getCredentialsFromFile($output);

        if (!empty($credentials)) {
            return $credentials;
        }

        return [
            'key' => $output->ask('Please enter your AWS user key'),
            'secret' => $output->ask('Please enter your AWS user secret'),
        ];
    }

    /**
     * Get the AWS credentials from the credentials file.
     */
    private function getCredentialsFromFile(OutputStyle $output): array
    {
        if (!is_string($this->credentialsFilePath)
            || !$output->confirm('Would you like to choose credentials from your AWS credentials file?')
        ) {
            return [];
        }

        $parsedCredentials = collect(parse_ini_file($this->credentialsFilePath, true));

        if ($parsedCredentials->isEmpty()) {
            return [];
        }

        $credentials = $output->choice(
            'Which set of credentials would you like to use?',
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
