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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\EmailIdentity;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

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
            ->setDescription('Create a new email identity')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the email identity')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The ID of the cloud provider where the email identity will be created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the email identity will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $name = $this->input->getStringArgument('name');

        if (empty($name)) {
            $name = $this->output->ask('What is the name of the email identity being created?');
        }

        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to create the email identity on?');
        $region = $this->fulfill(new RegionRequirement('Which region should the email identity be created in?'), ['provider' => $provider]);

        $identity = $this->apiClient->createEmailIdentity($provider, $name, $region);

        $this->output->info('Email identity created');

        if ('domain' === $identity->getType()) {
            $this->displayDkimAuthenticationRecords($identity);
        } elseif ('email' === $identity->getType()) {
            $this->output->newLine();
            $this->output->important(sprintf('A verification email was sent to %s to validate the email identity', $identity->getName()));
        }
    }

    /**
     * Display warning about DNS records required to authenticate the DKIM signature and verify it.
     */
    private function displayDkimAuthenticationRecords(EmailIdentity $identity): void
    {
        $dkimRecords = $identity->getDkimAuthenticationRecords();

        if (empty($dkimRecords) || $identity->isManaged()) {
            return;
        }

        $this->output->newLine();
        $this->output->important('The following DNS records needs to exist on your DNS server at all times to verify the email identity and authenticate its DKIM signature:');
        $this->output->newLine();
        $this->output->table(
            ['Name', 'Type', 'Value'],
            collect($dkimRecords)->map(function (array $dkimRecord) {
                return [
                    $dkimRecord['name'],
                    strtoupper($dkimRecord['type']),
                    $dkimRecord['value'],
                ];
            })->all()
        );
    }
}
