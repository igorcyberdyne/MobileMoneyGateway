<?php

namespace Ekolotech\MoMoGateway\Tests\Factory;

use Ekolotech\MoMoGateway\Factory\ApiGatewayFactory;
use Ekolotech\MoMoGateway\Model\Currency;
use Ekolotech\MoMoGateway\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessAndEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;
use Ekolotech\MoMoGateway\Tests\MtnGateway\MtnAuthenticationProductConfig;
use PHPUnit\Framework\TestCase;

class MtnApiAccessAndEnvironmentConfigService
    implements
    MtnApiAccessAndEnvironmentConfigInterface,
    MtnApiAccessConfigErrorListenerInterface
{
    public function getMtnAuthenticationProduct(): MtnAuthenticationProduct
    {
        return MtnAuthenticationProductConfig::collectionKeys();
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

    public function onApiUserCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
        // TODO: Implement onApiUserCreationError() method.
    }

    public function onApiKeyCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
        // TODO: Implement onApiKeyCreationError() method.
    }

    public function onTokenCreationError(MtnAuthenticationProduct $mtnAuthenticationProduct, array $data): void
    {
        // TODO: Implement onTokenCreationError() method.
    }
}
class ApiGatewayFactoryTest extends TestCase
{
    public static function gatewayProductTypeDataProvider(): array
    {
        return [
            "Mtn collection" => [
                "methodName" => "loadMtnCollectionGateway",
                "instanceExpectedOf" => CollectionGatewayInterface::class,
            ],
            "Mtn disbursement" => [
                "methodName" => "loadMtnDisbursementGateway",
                "instanceExpectedOf" => DisbursementGatewayInterface::class,
            ],
        ];
    }

    /**
     * @dataProvider gatewayProductTypeDataProvider
     * @param string $methodName
     * @param string $instanceExpectedOf
     * @return void
     */
    public function test_load_MtnGateway_product_THEN_success(
        string $methodName,
        string $instanceExpectedOf
    )
    {
        $object = new MtnApiAccessAndEnvironmentConfigService();
        $instance = ApiGatewayFactory::$methodName(
            $object,
            $object
        );

        $this->assertTrue($instance instanceof $instanceExpectedOf, "Expected instance of '$instanceExpectedOf'");
    }
}
