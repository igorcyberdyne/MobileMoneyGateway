<?php

namespace DemoApp\Service;

use DemoApp\Repository\MtnAccessRepositoryInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\SandboxCollectionGateway;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;

final class CollectionGatewayService extends SandboxCollectionGateway implements MtnApiAccessConfigListenerInterface
{
    public function __construct(
        private readonly MtnAccessRepositoryInterface $accessRepository
    )
    {
        parent::__construct(
            new MtnAuthenticationProduct(
            "65a9c425-1d54-4a7b-b7d4-9c756f681920",
            "0672b80420244d9f9d39330b0811e1cd",
            "d57e01802dd3456fbfc6c2998dca2426",
                $this->accessRepository->getApiKey()
            ),
            $this->accessRepository->getMtnAccessToken()
        );
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