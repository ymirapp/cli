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

use Ymir\Cli\ExecutionContext;

class CertificateDomainsRequirement extends AbstractRequirement
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): array
    {
        $domains = $context->getInput()->getArrayArgument('domains');

        if (empty($domains)) {
            $domains = array_map('trim', explode(',', (string) $context->getOutput()->ask($this->question)));
        }

        if (1 === count($domains) && false === stripos($domains[0], '*.') && $context->getOutput()->confirm(sprintf('Do you want your certificate to also cover "<comment>*.%s</comment>" subdomains?', $domains[0]))) {
            $domains[] = '*.'.$domains[0];
        } elseif (1 === count($domains) && 0 === stripos($domains[0], '*.')) {
            $domains[] = substr($domains[0], 2);
        }

        return $domains;
    }
}
