<?php

namespace Ekolotech\MoMoGateway\Api\Factory;

use Ekolotech\MoMoGateway\Api\Exception\FactoryException;
use Ekolotech\MoMoGateway\Api\Model\GatewayProductTypeEnum;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\AbstractCollectionGateway;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\AbstractDisbursementGateway;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;

abstract class ApiGatewayFactory
{
    /**
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @return CollectionGatewayInterface
     * @throws FactoryException
     */
    public static function loadMtnCollectionGateway(
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
    ) : CollectionGatewayInterface
    {
        return self::loadMtnGateway(
            GatewayProductTypeEnum::MtnCollectionGateway,
            $accessAndEnvironmentConfig
        );
    }
    /**
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @return DisbursementGatewayInterface
     * @throws FactoryException
     */
    public static function loadMtnDisbursementGateway(
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
    ) : DisbursementGatewayInterface
    {
        return self::loadMtnGateway(
            GatewayProductTypeEnum::MtnDisbursementGateway,
            $accessAndEnvironmentConfig
        );
    }


    /**
     * @param GatewayProductTypeEnum $gatewayProductTypeEnum
     * @param MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
     * @return CollectionGatewayInterface|DisbursementGatewayInterface
     * @throws FactoryException
     */
    private static function loadMtnGateway(
        GatewayProductTypeEnum $gatewayProductTypeEnum,
        MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig
    ) : CollectionGatewayInterface|DisbursementGatewayInterface
    {
        return match ($gatewayProductTypeEnum) {
            GatewayProductTypeEnum::MtnCollectionGateway => new class ($accessAndEnvironmentConfig) extends AbstractCollectionGateway
                implements MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
            {
                public function __construct(private readonly MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig)
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
            },
            GatewayProductTypeEnum::MtnDisbursementGateway => new class ($accessAndEnvironmentConfig) extends AbstractDisbursementGateway
                implements MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
            {
                public function __construct(private readonly MtnApiAccessAndEnvironmentConfigInterface $accessAndEnvironmentConfig)
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
            },
            default => throw FactoryException::load(FactoryException::CANNOT_CREATE_OBJECT_WITH_TYPE, ["type" => "[$gatewayProductTypeEnum->name]"]),
        };
    }
}