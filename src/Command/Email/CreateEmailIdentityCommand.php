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

namespace Ymir\Cli\Command\Email;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\ConsoleOutput;

class CreateEmailIdentityCommand extends AbstractEmailIdentityCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'email:identity:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new email identity')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the email identity')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the email identity will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the email identity will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $identity = $this->retryApi(function () use ($input, $output) {
            $name = $this->getStringArgument($input, 'name');

            if (empty($name) && $input->isInteractive()) {
                $name = $output->ask('What is the name of the email identity');
            }

            $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider where the email identity will be created', $input, $output);

            return $this->apiClient->createEmailIdentity($providerId, $name, $this->determineRegion('Enter the name of the region where the email identity will be created', $providerId, $input, $output));
        }, 'Do you want to try creating an email identity again?', $output);

        $output->info('Email identity created');

        if ('domain' === $identity['type'] && !$identity['managed']) {
            $this->showValidationRecord($identity['id'], $output);
        } elseif ('email' === $identity['type']) {
            $output->newLine();
            $output->warn(sprintf('A verification email was sent to %s to validate the email identity', $identity['name']));
        }
    }

    /**
     * Show the warning for adding DNS record manually.
     */
    private function showValidationRecord(int $identityId, ConsoleOutput $output)
    {
        $validationRecord = $this->wait(function () use ($identityId) {
            $identity = $this->apiClient->getEmailIdentity($identityId);

            return $identity['validation_record'] ?? [];
        });

        if (!isset($validationRecord['name'], $validationRecord['value'])) {
            return;
        }

        $output->newLine();
        $output->warn('The following DNS record needs to be manually added to your DNS server to validate the email identity:');
        $output->newLine();
        $output->table(
            ['Name', 'Value'],
            [$validationRecord]
        );
    }
}
