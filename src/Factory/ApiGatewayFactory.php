<?php

namespace Ekolotech\MoMoGateway\Factory;

use Ekolotech\MoMoGateway\Exception\FactoryException;
use Ekolotech\MoMoGateway\Model\GatewayProductTypeEnum;
use Ekolotech\MoMoGateway\MtnGateway\Collection\AbstractCollectionGateway;
use Ekolotech\MoMoGateway\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\AbstractDisbursementGateway;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiCollectionErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;

abstract class ApiGatewayFactory
{
    /**
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @param MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener
     * @param MtnApiCollectionErrorListenerInterface|null $mtnApiCollectionErrorListener
     * @return CollectionGatewayInterface
     * @throws FactoryException
     */
    public static function loadMtnCollectionGateway(
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig,
        MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener,
        ?MtnApiCollectionErrorListenerInterface $mtnApiCollectionErrorListener = null,
    ) : CollectionGatewayInterface
    {
        return self::loadMtnGateway(
            GatewayProductTypeEnum::MtnCollectionGateway,
            $accessAndEnvironmentConfig,
            $mtnApiAccessConfigErrorListener,
            $mtnApiCollectionErrorListener
        );
    }

    /**
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @param MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener
     * @return DisbursementGatewayInterface
     * @throws FactoryException
     */
    public static function loadMtnDisbursementGateway(
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig,
        MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener
    ) : DisbursementGatewayInterface
    {
        return self::loadMtnGateway(
            GatewayProductTypeEnum::MtnDisbursementGateway,
            $accessAndEnvironmentConfig,
            $mtnApiAccessConfigErrorListener
        );
    }


    /**
     * @param GatewayProductTypeEnum $gatewayProductTypeEnum
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @param MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener
     * @param MtnApiCollectionErrorListenerInterface|null $mtnApiCollectionErrorListener
     * @return CollectionGatewayInterface|DisbursementGatewayInterface
     * @throws FactoryException
     */
    private static function loadMtnGateway(
        GatewayProductTypeEnum $gatewayProductTypeEnum,
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig,
        MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener,
        ?MtnApiCollectionErrorListenerInterface $mtnApiCollectionErrorListener = null,
    ) : CollectionGatewayInterface|DisbursementGatewayInterface
    {
        return match ($gatewayProductTypeEnum) {
            GatewayProductTypeEnum::MtnCollectionGateway => new class (
                $accessAndEnvironmentConfig,
                $mtnApiAccessConfigErrorListener,
                $mtnApiCollectionErrorListener
            ) extends AbstractCollectionGateway
                implements
                MtnApiEnvironmentConfigInterface,
                MtnApiAccessConfigListenerInterface,
                MtnApiAccessConfigErrorListenerInterface,
                MtnApiCollectionErrorListenerInterface
            {
                public function __construct(
                    private readonly MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig,
                    private readonly ?MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener,
                    private readonly ?MtnApiCollectionErrorListenerInterface $mtnApiCollectionErrorListener,
                )
                {
                    parent::__construct(
                        $accessAndEnvironmentConfig->getMtnAuthenticationProduct(),
                        $accessAndEnvironmentConfig->getMtnAccessToken()
                    );
                }

                public function getBaseApiUrl(): string
                {
                    return $this->accessAndEnvironmentConfig->getBaseApiUrl();
                }

                public function getProviderCallbackUrl(): string
                {
                    return $this->accessAndEnvironmentConfig->getProviderCallbackUrl();
                }

                public function getProviderCallbackHost(): string
                {
                    return $this->accessAndEnvironmentConfig->getProviderCallbackHost();
                }

                public function isProd(): bool
                {
                    return $this->accessAndEnvironmentConfig->isProd();
                }

                public function getCurrency(): string
                {
                    return $this->accessAndEnvironmentConfig->getCurrency();
                }

                public function onApiUserCreated(string $apiUser): void
                {
                    $this->accessAndEnvironmentConfig->onApiUserCreated($apiUser);
                }

                public function onApiKeyCreated(string $apiKey): void
                {
                    $this->accessAndEnvironmentConfig->onApiKeyCreated($apiKey);
                }

                public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
                {
                    $this->accessAndEnvironmentConfig->onTokenCreated($mtnAccessToken);
                }

                public function onApiUserCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onApiUserCreationError($mtnAuthenticationProduct, $data);
                }

                public function onApiKeyCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onApiKeyCreationError($mtnAuthenticationProduct, $data);
                }

                public function onTokenCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onTokenCreationError($mtnAuthenticationProduct, $data);
                }

                public function onCollectError(string $reference, array $data): void
                {
                    $this->mtnApiCollectionErrorListener?->onCollectError($reference, $data);
                }

                public function onCollectReferenceError(string $reference, array $data): void
                {
                    $this->mtnApiCollectionErrorListener?->onCollectReferenceError($reference, $data);
                }

            },
            GatewayProductTypeEnum::MtnDisbursementGateway => new class (
                $accessAndEnvironmentConfig,
                $mtnApiAccessConfigErrorListener
            ) extends AbstractDisbursementGateway
                implements
                MtnApiEnvironmentConfigInterface,
                MtnApiAccessConfigListenerInterface,
                MtnApiAccessConfigErrorListenerInterface
            {
                public function __construct(
                    private readonly MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig,
                    private readonly ?MtnApiAccessConfigErrorListenerInterface $mtnApiAccessConfigErrorListener
                )
                {
                    parent::__construct(
                        $accessAndEnvironmentConfig->getMtnAuthenticationProduct(),
                        $accessAndEnvironmentConfig->getMtnAccessToken()
                    );
                }

                public function getBaseApiUrl(): string
                {
                    return $this->accessAndEnvironmentConfig->getBaseApiUrl();
                }

                public function getProviderCallbackUrl(): string
                {
                    return $this->accessAndEnvironmentConfig->getProviderCallbackUrl();
                }

                public function getProviderCallbackHost(): string
                {
                    return $this->accessAndEnvironmentConfig->getProviderCallbackHost();
                }

                public function isProd(): bool
                {
                    return $this->accessAndEnvironmentConfig->isProd();
                }

                public function getCurrency(): string
                {
                    return $this->accessAndEnvironmentConfig->getCurrency();
                }

                public function onApiUserCreated(string $apiUser): void
                {
                    $this->accessAndEnvironmentConfig->onApiUserCreated($apiUser);
                }

                public function onApiKeyCreated(string $apiKey): void
                {
                    $this->accessAndEnvironmentConfig->onApiKeyCreated($apiKey);
                }

                public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
                {
                    $this->accessAndEnvironmentConfig->onTokenCreated($mtnAccessToken);
                }

                public function onApiUserCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onApiUserCreationError($mtnAuthenticationProduct, $data);
                }

                public function onApiKeyCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onApiKeyCreationError($mtnAuthenticationProduct, $data);
                }

                public function onTokenCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
                {
                    $this->mtnApiAccessConfigErrorListener->onTokenCreationError($mtnAuthenticationProduct, $data);
                }
            },
            default => throw FactoryException::load(FactoryException::CANNOT_CREATE_OBJECT_WITH_TYPE, ["type" => "[$gatewayProductTypeEnum->name]"]),
        };
    }
}