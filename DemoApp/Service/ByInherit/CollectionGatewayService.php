<?php

namespace DemoApp\Service\ByInherit;

use DemoApp\Config;
use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Model\Currency;
use Ekolotech\MoMoGateway\MtnGateway\Collection\AbstractCollectionGateway;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;

final class CollectionGatewayService extends AbstractCollectionGateway implements MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
{
    public function __construct(
        private readonly MtnAccessRepositoryInterface $accessRepository
    )
    {
        parent::__construct(
            Config::collectionKeys(
                apiUser: $this->accessRepository->getApiUser(),
                apiKey: $this->accessRepository->getApiKey()
            ),
            $this->accessRepository->getMtnAccessToken()
        );
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
}