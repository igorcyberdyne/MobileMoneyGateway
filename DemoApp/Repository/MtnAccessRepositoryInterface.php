<?php

namespace DemoApp\Repository;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;

interface MtnAccessRepositoryInterface
{
    public function getApiUser(): ?string;

    public function saveApiUser(string $apiUser): static;

    public function getApiKey(): ?string;

    public function saveApiKey(string $apiKey): static;

    public function getMtnAccessToken(): ?MtnAccessToken;

    public function saveMtnAccessToken(MtnAccessToken $mtnAccessToken): static;
}