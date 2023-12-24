<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\Model\Currency;

class ProductionCollectionGateway extends AbstractCollectionGateway
{

    public function getProviderCallbackUrl(): string
    {
        return "https://callback.ekolopay.com/callback/mtn-cg";
    }

    public function getProviderCallbackHost(): string
    {
        return "callback.ekolopay.com";
    }

    public function isProd(): bool
    {
        return true;
    }

    public function getCurrency(): string
    {
        return Currency::XAF;
    }
}