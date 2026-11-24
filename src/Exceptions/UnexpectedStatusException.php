<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Exceptions;

use Daika7ana\Cargus\DTOs\Common\Errors\CargusError;
use Psr\Http\Message\ResponseInterface;

class UnexpectedStatusException extends CargusException
{
    public function __construct(
        public readonly ?ResponseInterface $response = null,
        string $message = '',
        int $code = 0,
        public readonly ?int $retryAfter = null,
        public readonly ?CargusError $error = null,
    ) {
        if ($message === '') {
            $message = $response === null
                ? 'Unexpected HTTP status'
                : "Unexpected HTTP status {$response->getStatusCode()}";
        }

        parent::__construct($message, $response?->getStatusCode() ?? $code);
    }
}
