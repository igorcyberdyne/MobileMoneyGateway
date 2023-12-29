<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;

interface MtnApiAccessConfigListenerInterface
{
    public function onApiUserCreated(): void;

    public function onApiKeyCreated(string $apiKey): void;

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void;

}