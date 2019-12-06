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

namespace Placeholder\Cli\Exception;

use GuzzleHttp\Exception\ClientException;
use Placeholder\Cli\Command\LoginCommand;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Exception\RuntimeException;

class ApiClientException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(ClientException $exception)
    {
        $message = $exception->getMessage();

        if (401 === $exception->getCode()) {
            $message = sprintf('Please authenticate using the "%s" command', LoginCommand::NAME);
        } elseif (402 === $exception->getCode()) {
            $message = 'An active subscription is required to perform this action';
        } elseif (403 === $exception->getCode()) {
            $message = 'You are not authorized to perform this action';
        } elseif (404 === $exception->getCode()) {
            $message = 'The requested resource does not exist';
        } elseif (409 === $exception->getCode()) {
            $message = 'This operation is already in progress';
        } elseif (410 === $exception->getCode()) {
            $message = 'The requested resource is being deleted';
        } elseif (429 === $exception->getCode()) {
            $message = 'You are attempting this action too often';
        } elseif (in_array($exception->getCode(), [400, 422])) {
            $message = $this->getValidationErrorMessage($exception);
        }

        parent::__construct($message, $exception->getCode());
    }

    /**
     * Get the validation error messages from the ClientException.
     */
    private function getValidationErrorMessage(ClientException $exception): string
    {
        $message = 'There were some problems with your request';
        $response = $exception->getResponse();

        if (!$response instanceof ResponseInterface) {
            return $message;
        }

        $errors = collect(json_decode((string) $response->getBody(), true))->only('errors')->flatten();

        if ($errors->isEmpty()) {
            return $message;
        }

        $message .= ":\n";

        foreach ($errors as $index => $error) {
            $message .= "\n    * {$error}";
        }

        return $message;
    }
}
