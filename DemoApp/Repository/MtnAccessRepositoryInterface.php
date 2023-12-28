<?php

namespace DemoApp\Repository;

use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAccessToken;

interface MtnAccessRepositoryInterface
{
    public function getApiKey() : ?string;
    public function saveApiKey(string $apiKey) : static;
    public function getMtnAccessToken() : ?MtnAccessToken;
    public function saveMtnAccessToken(MtnAccessToken $mtnAccessToken) : static;
}