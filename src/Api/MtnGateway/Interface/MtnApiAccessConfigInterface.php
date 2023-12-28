<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Api\Interface\ApiGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;

interface MtnApiAccessConfigInterface extends ApiGatewayInterface
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

}