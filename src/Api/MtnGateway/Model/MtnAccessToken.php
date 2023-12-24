<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model;

class MtnAccessToken
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $tokenType,
        private readonly int    $expiresIn,
        private readonly bool   $isExpired = false,
    )
    {
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->isExpired;
    }

}