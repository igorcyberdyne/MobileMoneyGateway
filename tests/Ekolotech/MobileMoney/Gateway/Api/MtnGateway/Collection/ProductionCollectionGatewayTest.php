<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;

class ProductionCollectionGatewayTest extends TestCase
{
    public function urlDataProvider(): array
    {
        return [
            [
                "method" => "getBaseApiUrl",
                "data" => "https://proxy.momoapi.mtn.com",
            ],
            [
                "method" => "currentApiEnvName",
                "data" => "mtncongo",
            ],
            [
                "method" => "getProviderCallbackUrl",
                "data" => "https://callback.ekolopay.com/callback/mtn-cg",
            ],
            [
                "method" => "getProviderCallbackHost",
                "data" => "callback.ekolopay.com",
            ],
            [
                "method" => "isProd",
                "data" => true,
            ],
            [
                "method" => "getCurrency",
                "data" => "XAF",
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
    public function test_url_for_prod_environment(string $method, $data)
    {
        $collectionGateway = new ProductionCollectionGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $collectionGateway->$method());
    }

}
