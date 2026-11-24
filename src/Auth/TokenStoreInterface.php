<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Auth;

interface TokenStoreInterface
{
    public function getToken(): ?Token;

    public function setToken(Token $token): void;
}
