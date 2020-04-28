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

namespace Ymir\Cli\Exception;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\Command\LoginCommand;

class ApiClientException extends RuntimeException
{
    /**
     * Constructor.
     */
    public function __construct(ClientException $exception)
    {
        $message = $this->getApiErrorMessage($exception);

        if (empty($message)) {
            $message = $this->getDefaultMessage($exception->getCode());
        } elseif (in_array($exception->getCode(), [400, 422])) {
            $message = $this->getValidationErrorMessage($exception);
        }

        parent::__construct($message, $exception->getCode());
    }

    /**
     * Get the Ymir API error message.
     */
    private function getApiErrorMessage(ClientException $exception): string
    {
        $message = '';
        $response = $exception->getResponse();

        if (!$response instanceof ResponseInterface) {
            return $message;
        }

        return str_replace('"', '', (string) $response->getBody());
    }

    /**
     * Get the default exception message based on the exception code.
     */
    private function getDefaultMessage(int $code): string
    {
        $message = '';

        if (401 === $code) {
            $message = sprintf('Please authenticate using the "%s" command', LoginCommand::NAME);
        } elseif (402 === $code) {
            $message = 'An active subscription is required to perform this action';
        } elseif (403 === $code) {
            $message = 'You are not authorized to perform this action';
        } elseif (404 === $code) {
            $message = 'The requested resource does not exist';
        } elseif (409 === $code) {
            $message = 'This operation is already in progress';
        } elseif (410 === $code) {
            $message = 'The requested resource is being deleted';
        } elseif (429 === $code) {
            $message = 'You are attempting this action too often';
        }

        return $message;
    }

    /**
     * Get the validation error messages from the ClientException.
     */
    private function getValidationErrorMessage(ClientException $exception): string
    {
        $message = 'The Ymir API responded with errors';
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
