<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Support;

use Psr\Http\Message\ResponseInterface;

/**
 * Binary download (AWB label, invoice or order PDF).
 */
final readonly class Document
{
    public function __construct(
        public string $contents,
        public ?string $mimeType = null,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            contents: (string) $response->getBody(),
            mimeType: $response->getHeaderLine('Content-Type') ?: null,
        );
    }

    public function isPdf(): bool
    {
        return str_starts_with($this->contents, '%PDF');
    }
}
