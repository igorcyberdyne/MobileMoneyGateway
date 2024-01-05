<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Interface\ApiGatewayInterface;

interface MtnApiEnvironmentConfigInterface extends ApiGatewayInterface
{
    public function getProviderCallbackUrl(): string;

    public function getProviderCallbackHost(): string;

    public function isProd(): bool;

    public function getCurrency(): string;
}