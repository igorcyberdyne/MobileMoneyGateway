<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Interface;

use Ekolotech\MobileMoney\Gateway\Api\Interface\ApiGatewayInterface;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAccessToken;

interface MtnApiGatewayInterface extends ApiGatewayInterface
{
    public function getApiUser(): array;
    public function createApiUser(): bool;

    public function createApiKey(): string;

    public function createToken(): MtnAccessToken;

    /**
     * Help method to create apiUser, apiKey and token
     * TODO
     * @return array
     */
    public function createAccess(): array;

    public function accountHolderActive(string $phoneNumber): bool;

    public function accountHolderBasicUserInfo(string $phoneNumber): array;

}