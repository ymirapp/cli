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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractInvocationCommand;
use Ymir\Cli\Project\Configuration\DomainConfigurationChange;
use Ymir\Cli\Support\Arr;

class ChangeEnvironmentDomainCommand extends AbstractInvocationCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:domain:change';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change an environment\'s domain')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to change the domain of', 'staging')
            ->addArgument('domain', InputArgument::OPTIONAL, 'The current environment domain to replace');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $currentDomain = $this->input->getStringArgument('domain');
        $environment = $this->input->getStringArgument('environment');
        $vanityDomain = $this->apiClient->getEnvironmentVanityDomainName($this->projectConfiguration->getProjectId(), $environment);

        if (empty($currentDomain) && $this->output->confirm(sprintf('Do you want to use the "<comment>%s</comment>" vanity domain as the current environment domain to replace?', $vanityDomain))) {
            $currentDomain = $vanityDomain;
        } elseif (empty($currentDomain)) {
            $currentDomain = strtolower((string) $this->output->ask('What is the current environment domain that you want to replace?'));
        }

        $domainOption = Arr::get($this->projectConfiguration->getEnvironment($environment), 'domain');
        $newDomain = '';

        if (is_string($domainOption)) {
            $newDomain = $domainOption;
        } elseif (is_array($domainOption)) {
            $newDomain = strtolower((string) $this->output->choice(sprintf('Which mapped domain do you want to use as the new "<comment>%s</comment>" environment domain?', $environment), $domainOption));
        } elseif (empty($domainOption) && $currentDomain !== $vanityDomain && $this->output->confirm(sprintf('Do you want to use the "<comment>%s</comment>" vanity domain as the new "<comment>%s</comment>" environment domain?', $vanityDomain, $environment))) {
            $newDomain = $vanityDomain;
        } elseif (empty($domainOption)) {
            $newDomain = strtolower((string) $this->output->ask(sprintf('What is the new domain that you want to use as the new "<comment>%s</comment>" environment domain?', $environment)));
        }

        $currentDomain = parse_url($currentDomain, PHP_URL_HOST) ?? $currentDomain;
        $newDomain = parse_url($newDomain, PHP_URL_HOST) ?? $newDomain;

        if (!is_string($currentDomain)) {
            throw new RuntimeException('Unable to parse the current environment domain');
        } elseif (!is_string($newDomain)) {
            throw new RuntimeException('Unable to parse the new environment domain');
        } elseif ($currentDomain === $newDomain) {
            throw new RuntimeException('Both current and new environment domain are identical');
        }

        if (!$this->output->confirm(sprintf('Are you sure you want to change the "<comment>%s</comment>" environment domain from "<comment>%s</comment>" to "<comment>%s</comment>"?', $environment, $currentDomain, $newDomain), false)) {
            return;
        }

        if ($newDomain !== $vanityDomain && !Arr::has((array) $domainOption, $newDomain) && $this->output->confirm(sprintf('Do you want to add the "<comment>%s</comment>" domain to your "<comment>%s</comment>" environment configuration?', $newDomain, $environment))) {
            $this->projectConfiguration->applyChangesToEnvironment($environment, new DomainConfigurationChange($newDomain));
        }

        $this->output->info(sprintf('Changing "<comment>%s</comment>" environment domain from "<comment>%s</comment>" to "<comment>%s</comment>"', $environment, $currentDomain, $newDomain));

        $result = $this->invokeWpCliCommand(sprintf('wp search-replace %s %s --all-tables', $currentDomain, $newDomain), $environment);

        if (!isset($result['exitCode']) || 0 !== $result['exitCode']) {
            throw new RuntimeException('WP-CLI search-replace command failed');
        }

        $this->output->info('Flushing object cache');

        $this->invokeWpCliCommand('wp cache flush', $environment, 0);

        $this->output->info('Environment domain changed');
    }
}
