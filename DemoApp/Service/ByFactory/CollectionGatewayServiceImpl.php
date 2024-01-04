<?php

namespace DemoApp\Service\ByFactory;

use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;

final class CollectionGatewayServiceImpl implements MtnApiAccessAndEnvironmentConfigInterface
{
    public function __construct(
        private readonly MtnAccessRepositoryInterface $accessRepository
    )
    {
    }

    public function getBaseApiUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
    }

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

    public function onApiUserCreated(string $apiUser): void
    {
        $this->accessRepository->saveApiUser($apiUser);
    }

    public function onApiKeyCreated(string $apiKey): void
    {
        $this->accessRepository->saveApiKey($apiKey);
    }

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
    {
        $this->accessRepository->saveMtnAccessToken($mtnAccessToken);
    }

    public function getMtnAuthenticationProduct(): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            "0672b80420244d9f9d39330b0811e1cd",
            "d57e01802dd3456fbfc6c2998dca2426",
            $this->accessRepository->getApiUser(),
            $this->accessRepository->getApiKey()
        );
    }

    public function getMtnAccessToken(): ?MtnAccessToken
    {
        return $this->accessRepository->getMtnAccessToken();
    }
}