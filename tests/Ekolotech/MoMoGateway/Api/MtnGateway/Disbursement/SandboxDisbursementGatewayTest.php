<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;

class SandboxDisbursementGatewayTest extends TestCase
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
        $disbursementGateway = new SandboxDisbursementGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $disbursementGateway->$method());
    }
}
