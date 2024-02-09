<?php

namespace DemoApp\Service\ByFactory;

use DemoApp\Config;
use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Model\Currency;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;

final class DisbursementGatewayServiceImpl
    implements
    MtnApiAccessAndEnvironmentConfigInterface,
    MtnApiAccessConfigErrorListenerInterface
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
        return Config::disbursementKeys(
            apiUser: $this->accessRepository->getApiUser(),
            apiKey: $this->accessRepository->getApiKey()
        );
    }

    public function getMtnAccessToken(): ?MtnAccessToken
    {
        return $this->accessRepository->getMtnAccessToken();
    }

    public function onApiUserCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
    }

    public function onApiKeyCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
    }

    public function onTokenCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
    }
}