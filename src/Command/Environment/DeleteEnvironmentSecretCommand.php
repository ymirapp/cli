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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Secret;

class DeleteEnvironmentSecretCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:secret:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete an environment\'s secret')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment where the secret is')
            ->addArgument('secret', InputArgument::OPTIONAL, 'The ID or name of the secret');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to delete a secret from?');
        $secrets = $this->apiClient->getSecrets($this->getProject(), $environment);

        if ($secrets->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The "%s" environment has no secrets', $environment->getName()));
        }

        $secretIdOrName = $this->input->getStringArgument('secret');

        if (empty($secretIdOrName)) {
            $secretIdOrName = (string) $this->output->choice('Which secret would you like to delete?', $secrets->map(function (Secret $secret) {
                return $secret->getName();
            }));
        }

        $secret = $secrets->firstWhereIdOrName($secretIdOrName);

        if (!$secret instanceof Secret) {
            throw new ResourceNotFoundException('secret', $secretIdOrName);
        } elseif (!$this->output->confirm('Are you sure you want to delete this secret?', false)) {
            return;
        }

        $this->apiClient->deleteSecret($secret);

        $this->output->info('Secret deleted');
    }
}
