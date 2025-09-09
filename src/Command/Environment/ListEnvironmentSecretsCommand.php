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

use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Secret;

class ListEnvironmentSecretsCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:secret:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List an environment\'s secrets')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to list secrets of');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to list the secrets of?');

        $this->output->table(
            ['Id', 'Name', 'Last Updated'],
            $this->apiClient->getSecrets($this->getProject(), $environment)->map(function (Secret $secret) {
                return [$secret->getId(), $secret->getName(), Carbon::parse($secret->getUpdatedAt())->diffForHumans()];
            })->all()
        );
    }
}
