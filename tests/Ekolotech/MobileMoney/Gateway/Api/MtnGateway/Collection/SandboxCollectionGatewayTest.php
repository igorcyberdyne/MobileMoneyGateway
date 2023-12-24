<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;

class SandboxCollectionGatewayTest extends TestCase
{
    public function urlDataProvider(): array
    {
        return [
            [
                "method" => "getBaseApiUrl",
                "data" => "https://sandbox.momodeveloper.mtn.com",
            ],
            [
                "method" => "currentApiEnvName",
                "data" => "sandbox",
            ],
            [
                "method" => "getProviderCallbackUrl",
                "data" => "https://sandbox.momodeveloper.mtn.com",
            ],
            [
                "method" => "getProviderCallbackHost",
                "data" => "sandbox.momodeveloper.mtn.com",
            ],
            [
                "method" => "isProd",
                "data" => false,
            ],
            [
                "method" => "getCurrency",
                "data" => "EUR",
            ],
        ];
    }


    protected function givenAuthenticationProduct(
        string $apiUser = "apiUser",
        string $subscriptionKeyOne = "subscriptionKeyOne",
        string $subscriptionKeyTwo = "subscriptionKeyTwo",
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            $apiUser,
            $subscriptionKeyOne,
            $subscriptionKeyTwo,
            $apiKey,
        );
    }

    /**
     * @dataProvider urlDataProvider
     * @throws Exception
     */
    public function test_url_for_local_environment(string $method, $data)
    {
        $collectionGateway = new SandboxCollectionGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $collectionGateway->$method());
    }

}
