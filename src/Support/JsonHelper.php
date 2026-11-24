<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Support;

use JsonException;

final class JsonHelper
{
    /**
     * @throws JsonException
     */
    public static function decode(string $json): mixed
    {
        return json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public static function encode(mixed $value): string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
