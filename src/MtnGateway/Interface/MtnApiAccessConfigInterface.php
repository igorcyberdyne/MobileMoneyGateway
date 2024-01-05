<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Interface\ApiGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;

interface MtnApiAccessConfigInterface extends ApiGatewayInterface
{
    public function getApiUser(): array;

    public function createApiUser(): bool;

    public function createApiKey(): string;

    public function createToken(): MtnAccessToken;
}