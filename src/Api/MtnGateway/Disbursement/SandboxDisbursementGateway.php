<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Api\Model\Currency;

class SandboxDisbursementGateway extends AbstractDisbursementGateway
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