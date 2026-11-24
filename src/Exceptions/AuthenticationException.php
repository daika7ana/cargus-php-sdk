<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Exceptions;

use Daika7ana\Cargus\DTOs\Common\Errors\CargusError;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AuthenticationException extends CargusException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
        public readonly ?CargusError $error = null,
    ) {
        parent::__construct($message ?: 'Authentication failed', $response?->getStatusCode() ?? $code, $previous);
    }
}
