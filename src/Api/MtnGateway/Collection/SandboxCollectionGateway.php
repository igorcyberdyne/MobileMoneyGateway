<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Api\Model\Currency;

class SandboxCollectionGateway extends AbstractCollectionGateway
{
    public function getProviderCallbackUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
    }

    public function getProviderCallbackHost(): string
    {
        return "sandbox.momodeveloper.mtn.com";
    }

    public function isProd(): bool
    {
        return false;
    }

    public function getCurrency(): string
    {
        return Currency::EUR;
    }
}