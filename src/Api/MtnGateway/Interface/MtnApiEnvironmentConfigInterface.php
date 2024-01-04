<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Api\Interface\ApiGatewayInterface;

interface MtnApiEnvironmentConfigInterface extends ApiGatewayInterface
{
    public function getProviderCallbackUrl(): string;

    public function getProviderCallbackHost(): string;

    public function isProd(): bool;

    public function getCurrency(): string;
}