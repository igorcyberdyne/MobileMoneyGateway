<?php

namespace DemoApp\Service;

use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\AbstractDisbursementGateway;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;

final class DisbursementGatewayService extends AbstractDisbursementGateway implements MtnApiAccessConfigListenerInterface
{
    public function __construct(
        private readonly MtnAccessRepositoryInterface $accessRepository
    )
    {
        parent::__construct(
            new MtnAuthenticationProduct(
                "ea4d4ba0-e1ac-47d7-b0f1-ba672533f517",
                "ac4f92d8be3e4801bd346d7a986cff52",
                "a882e46cedd948b1abe31c513e4b822b",
                $this->accessRepository->getApiKey()
            ),
            $this->accessRepository->getMtnAccessToken()
        );
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

    public function onApiUserCreated(): void
    {
        // TODO: Implement onApiUserCreated() method.
        var_dump("------------- onApiUserCreated -------------");
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