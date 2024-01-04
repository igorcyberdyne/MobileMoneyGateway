<?php

namespace DemoApp\Service\ByInherit;

use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\AbstractDisbursementGateway;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;

final class DisbursementGatewayService extends AbstractDisbursementGateway implements MtnApiAccessConfigListenerInterface, MtnApiEnvironmentConfigInterface
{
    public function __construct(
        private readonly MtnAccessRepositoryInterface $accessRepository
    )
    {
        parent::__construct(
            new MtnAuthenticationProduct(
                "ac4f92d8be3e4801bd346d7a986cff52",
                "a882e46cedd948b1abe31c513e4b822b",
                $this->accessRepository->getApiUser(),
                $this->accessRepository->getApiKey()
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