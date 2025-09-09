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

namespace Ymir\Cli\Resource\Requirement;

use Illuminate\Support\Collection;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;

class AwsCredentialsRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): array
    {
        $profiles = null;
        $credentialsFilePath = $context->getHomeDirectory().'/.aws/credentials';
        $output = $context->getOutput();

        if (is_file($credentialsFilePath)) {
            $profiles = collect(parse_ini_file($credentialsFilePath, true))->filter(function ($profile): bool {
                return is_array($profile) && !empty($profile['aws_access_key_id']) && !empty($profile['aws_secret_access_key']);
            });
        }

        if (!$profiles instanceof Collection || $profiles->isEmpty()) {
            return $this->askCredentials($output);
        }

        $output->writeln('Available AWS credential profiles:');
        $output->list($profiles->keys());

        $selectedProfile = $output->ask('Which profile name would you like to use? (Press Enter to enter credentials manually)');

        return empty($profiles[$selectedProfile]) ? $this->askCredentials($output) : [
            'key' => $profiles[$selectedProfile]['aws_access_key_id'],
            'secret' => $profiles[$selectedProfile]['aws_secret_access_key'],
        ];
    }

    /**
     * Ask the user for AWS credentials manually.
     */
    private function askCredentials(Output $output): array
    {
        return [
            'key' => $output->ask('What is your AWS access key ID?'),
            'secret' => $output->askHidden('What is your AWS secret access key?'),
        ];
    }
}
