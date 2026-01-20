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

namespace Ymir\Cli\Command\WordPress;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\InvocationException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\Project\Configuration\DomainConfigurationChange;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Resource\Model\Environment;

class ChangeDomainCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    use HandlesWpCliInvocationTrait;

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'wordpress:change-domain';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change an environment\'s domain')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to change the domain of')
            ->addArgument('domain', InputArgument::OPTIONAL, 'The current environment domain to replace');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        if (!$this->getProjectConfiguration()->getProjectType() instanceof AbstractWordPressProjectType) {
            throw new UnsupportedProjectException('You can only use this command with WordPress, Bedrock or Radicle projects');
        }

        $environment = $this->resolve(Environment::class, 'Which <comment>%s</comment> environment would you like to change the domain of?');

        $currentDomain = $this->input->getStringArgument('domain');
        $vanityDomain = $environment->getVanityDomainName();

        if (empty($currentDomain) && $this->output->confirm(sprintf('Do you want to use the "<comment>%s</comment>" vanity domain as the current environment domain to replace?', $vanityDomain))) {
            $currentDomain = $vanityDomain;
        } elseif (empty($currentDomain)) {
            $currentDomain = strtolower((string) $this->output->ask('What is the current environment domain that you want to replace?'));
        }

        $domains = $this->getProjectConfiguration()->getEnvironmentConfiguration($environment->getName())->getDomains();
        $newDomain = '';

        if (1 === count($domains)) {
            $newDomain = $domains[0];
        } elseif (1 < count($domains)) {
            $newDomain = strtolower((string) $this->output->choice(sprintf('Which mapped domain do you want to use as the new "<comment>%s</comment>" environment domain?', $environment->getName()), $domains));
        } elseif (empty($domains) && $currentDomain !== $vanityDomain && $this->output->confirm(sprintf('Do you want to use the "<comment>%s</comment>" vanity domain as the new "<comment>%s</comment>" environment domain?', $vanityDomain, $environment->getName()))) {
            $newDomain = $vanityDomain;
        } elseif (empty($domains)) {
            $newDomain = strtolower((string) $this->output->ask(sprintf('What is the new domain that you want to use as the new "<comment>%s</comment>" environment domain?', $environment->getName())));
        }

        $currentDomain = parse_url($currentDomain, PHP_URL_HOST) ?? $currentDomain;
        $newDomain = parse_url($newDomain, PHP_URL_HOST) ?? $newDomain;

        if (!is_string($currentDomain)) {
            throw new InvalidInputException('Unable to parse the current environment domain');
        } elseif (!is_string($newDomain)) {
            throw new InvalidInputException('Unable to parse the new environment domain');
        } elseif ($currentDomain === $newDomain) {
            throw new InvalidInputException('Both current and new environment domain are identical');
        }

        if (!$this->output->confirm(sprintf('Are you sure you want to change the "<comment>%s</comment>" environment domain from "<comment>%s</comment>" to "<comment>%s</comment>"?', $environment->getName(), $currentDomain, $newDomain), false)) {
            return;
        }

        if ($newDomain !== $vanityDomain && !in_array($newDomain, $domains) && $this->output->confirm(sprintf('Do you want to add the "<comment>%s</comment>" domain to your "<comment>%s</comment>" environment configuration?', $newDomain, $environment->getName()))) {
            $this->getProjectConfiguration()->applyChangesToEnvironment($environment->getName(), new DomainConfigurationChange($newDomain));
        }

        $this->output->info(sprintf('Changing "<comment>%s</comment>" environment domain from "<comment>%s</comment>" to "<comment>%s</comment>"', $environment->getName(), $currentDomain, $newDomain));

        $result = $this->invokeWpCliCommand($this->getProject(), sprintf('wp search-replace %s %s --all-tables', $currentDomain, $newDomain), $environment);

        if (!isset($result['exitCode']) || 0 !== $result['exitCode']) {
            throw new InvocationException('WP-CLI search-replace command failed');
        }

        $this->output->info('Flushing object cache');

        $this->invokeWpCliCommand($this->getProject(), 'wp cache flush', $environment, 0);

        $this->output->info('Environment domain changed');
    }
}
