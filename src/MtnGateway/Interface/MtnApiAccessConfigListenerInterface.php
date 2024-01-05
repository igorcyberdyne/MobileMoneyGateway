<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;

interface MtnApiAccessConfigListenerInterface
{
    public function onApiUserCreated(string $apiUser): void;

    public function onApiKeyCreated(string $apiKey): void;

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void;

}