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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

class CreateEmailIdentityCommand extends AbstractCommand
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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the email identity')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider where the email identity will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the email identity will be located')
            ->setDescription('Create a new email identity');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $name = $this->getStringArgument($input, 'name');

        $providerId = $this->determineCloudProvider($input, $output, 'Enter the ID of the cloud provider where the email identity will be created');
        $identity = $this->apiClient->createEmailIdentity($providerId, $name, $this->determineRegion($input, $output, $providerId, 'Enter the name of the region where the email identity will be created'));

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
    protected function showValidationRecord(int $identityId, OutputStyle $output)
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
