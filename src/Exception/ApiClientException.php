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
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Command\LoginCommand;

class ApiClientException extends RuntimeException
{
    /**
     * The Guzzle response.
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * Constructor.
     */
    public function __construct(ClientException $exception)
    {
        $this->response = $exception->getResponse();

        $message = $this->getApiErrorMessage();

        if (empty($message)) {
            $message = $this->getDefaultMessage($exception->getCode());
        } elseif (in_array($exception->getCode(), [400, 422])) {
            $message = $this->getValidationErrorMessage();
        }

        parent::__construct($message, $exception->getCode());
    }

    /**
     * Get the validation errors that the API sent back.
     */
    public function getValidationErrors(): Collection
    {
        return collect(json_decode((string) $this->response->getBody(), true))->only('errors')->collapse();
    }

    /**
     * Get the Ymir API error message.
     */
    private function getApiErrorMessage(): string
    {
        $body = (string) $this->response->getBody();
        $decodedBody = json_decode($body, true);

        return JSON_ERROR_NONE === json_last_error() && !empty($decodedBody['message']) ? $decodedBody['message'] : str_replace('"', '', $body);
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
    private function getValidationErrorMessage(): string
    {
        $body = collect(json_decode((string) $this->response->getBody(), true));
        $errors = $body->only('errors')->flatten();
        $message = 'The Ymir API responded with errors';

        if ($errors->isEmpty() && $body->has('message')) {
            $errors->add($body->get('message'));
        } elseif ($errors->isEmpty() && !$body->has('message')) {
            return $message;
        }

        $message .= ":\n";

        foreach ($errors as $error) {
            $message .= "\n    * {$error}";
        }

        return $message;
    }
}
