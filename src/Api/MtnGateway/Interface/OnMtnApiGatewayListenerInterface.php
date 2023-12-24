<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Interface;

use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAccessToken;

interface OnMtnApiGatewayListenerInterface
{
    public function onApiUserCreated() : void;
    public function onApiKeyCreated(string $apiKey) : void;
    public function onTokenCreated(MtnAccessToken $mtnAccessToken) : void;

}