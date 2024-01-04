<?php

namespace Ekolotech\MoMoGateway\Api\Factory;

use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\Model\GatewayProductTypeEnum;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;

class MtnApiAccessAndEnvironmentConfigService implements MtnApiAccessAndEnvironmentConfigInterface
{
    public function getMtnAuthenticationProduct(): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            "0672b80420244d9f9d39330b0811e1cd",
            "d57e01802dd3456fbfc6c2998dca2426",
            "65a9c425-1d54-4a7b-b7d4-9c756f681920"
        );
    }

    public function getBaseApiUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
    }

    public function getMtnAccessToken(): ?MtnAccessToken
    {
        return null;
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
        // TODO: Implement onApiUserCreated() method.
    }

    public function onApiKeyCreated(string $apiKey): void
    {
        // TODO: Implement onApiUserCreated() method.
    }

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
    {
        // TODO: Implement onApiUserCreated() method.
    }
}
class ApiGatewayFactoryTest extends TestCase
{
    public function gatewayProductTypeDataProvider(): array
    {
        return [
            "Mtn collection" => [
                "gatewayType" => GatewayProductTypeEnum::MtnCollectionGateway->name,
                "instanceExpectedOf" => CollectionGatewayInterface::class,
            ],
            "Mtn disbursement" => [
                "gatewayType" => GatewayProductTypeEnum::MtnDisbursementGateway->name,
                "instanceExpectedOf" => DisbursementGatewayInterface::class,
            ],
        ];
    }

    /**
     * @dataProvider gatewayProductTypeDataProvider
     * @param string $gatewayType
     * @param string $instanceExpectedOf
     * @return void
     * @throws Exception
     */
    public function test_load_MtnGateway_product_THEN_success(string $gatewayType, string $instanceExpectedOf)
    {
        $instance = ApiGatewayFactory::loadMtnGateway(
            $gatewayType,
            new MtnApiAccessAndEnvironmentConfigService()
        );

        $this->assertTrue($instance instanceof $instanceExpectedOf);

    }
}
