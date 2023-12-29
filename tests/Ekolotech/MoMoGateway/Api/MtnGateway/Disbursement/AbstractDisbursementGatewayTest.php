<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Api\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Api\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Api\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;

class TestDisbursementGateway extends AbstractDisbursementGateway
{
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
}
class AbstractDisbursementGatewayTest extends TestCase
{

    private AbstractDisbursementGateway $disbursementGateway;
    private string $apiUser;
    private static MtnAuthenticationProduct $authenticationProduct;

    protected function setUp(): void
    {
        parent::setUp();

        static::$authenticationProduct = new MtnAuthenticationProduct(
            "ea4d4ba0-e1ac-47d7-b0f1-ba672533f517",
            "ac4f92d8be3e4801bd346d7a986cff52",
            "a882e46cedd948b1abe31c513e4b822b",
        );
    }

    private function givenApiUser(): string
    {
        $this->apiUser = AbstractTools::uuid();

        return $this->apiUser;
    }

    private function givenAuthenticationProduct(
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            $apiUser ?? static::$authenticationProduct->getApiUser(),
            static::$authenticationProduct->getSubscriptionKeyOne(),
            static::$authenticationProduct->getSubscriptionKeyTwo(),
            $apiKey
        );
    }

    /**
     * @throws Exception
     */
    private function givenDisburseGateway(MtnAuthenticationProduct $auth): static
    {
        $this->disbursementGateway = new TestDisbursementGateway($auth);

        return $this;
    }


    /**
     * @return void
     * @throws Exception
     */
    public function createApiUser(): void
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->givenApiUser(),
            apiKey: "apiKey"
        );

        $this
            ->givenDisburseGateway($auth)
            ->disbursementGateway
            ->createApiUser();
        $this->assertEquals($this->apiUser, $auth->getApiUser());

        $response = $this->disbursementGateway->getApiUser();
        $this->assertIsArray($response);
        $this->assertEquals(
            [
                "providerCallbackHost" => "sandbox.momodeveloper.mtn.com",
                "targetEnvironment" => "sandbox"
            ],
            [
                "providerCallbackHost" => $this->disbursementGateway->getProviderCallbackHost(),
                "targetEnvironment" => $this->disbursementGateway->currentApiEnvName()
            ],
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    public function createApiKeyAssociateToApiUser(): string
    {
        $this->createApiUser();

        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->apiUser,
        );

        $apiKey = $this
            ->givenDisburseGateway($auth)
            ->disbursementGateway
            ->createApiKey();

        $this->assertNotEmpty($apiKey);

        return $apiKey;
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function createToken(): static
    {
        $apiKey = $this->createApiKeyAssociateToApiUser();

        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->apiUser,
            apiKey: $apiKey
        );

        $mtnAccessToken = $this
            ->givenDisburseGateway($auth)
            ->disbursementGateway
            ->createToken();

        $this->assertNotEmpty($mtnAccessToken);

        return $this;
    }


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

    /**
     * @dataProvider urlDataProvider
     * @throws Exception
     */
    public function test_url_for_local_environment(string $method, $data)
    {
        $disbursementGateway = new TestDisbursementGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $disbursementGateway->$method());
    }

    /**
     * @throws Exception
     */
    public function test_baseUrl_and_productType()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->givenApiUser(),
            apiKey: "apiKey"
        );

        $this->assertEquals(
            "https://sandbox.momodeveloper.mtn.com",
            $this->givenDisburseGateway($auth)->disbursementGateway->getBaseApiUrl()
        );
    }

    /**
     * @throws Exception
     */
    public function test_create_apiUser_and_get_THEN_created()
    {
        $this->createApiUser();
    }

    /**
     * @throws Exception
     */
    public function test_create_apiKey_WHITHOUT_associate_to_apiUser_THEN_failed()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->givenApiUser(),
            apiKey: "apiKey"
        );

        $this->expectException(Exception::class);
        $this
            ->givenDisburseGateway($auth)
            ->disbursementGateway
            ->createApiKey();
    }

    /**
     * @throws Exception
     */
    public function test_create_apiKey_WHITH_associate_to_apiUser_THEN_create()
    {
        $this->createApiKeyAssociateToApiUser();
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function test_create_token_THEN_failed()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->givenApiUser(),
            apiKey: "apiKey"
        );

        $this->expectException(TokenCreationException::class);
        $this
            ->givenDisburseGateway($auth)
            ->disbursementGateway
            ->createToken();
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function test_create_token_THEN_created()
    {
        $this->createToken();
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function test_disburse_THEN_success()
    {
        $this->createToken();

        $disburseRequest = new DisburseRequestBody(
            1,
            "46733123452",
            AbstractTools::uuid()
        );

        $this->assertTrue($this->disbursementGateway->disburse($disburseRequest));

        return $disburseRequest->reference;
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function test_disburse_GIVEN_zero_as_amount_THEN_expected_exception()
    {
        $this->createToken();

        $disburseRequest = new DisburseRequestBody(
            0,
            "46733123452",
            AbstractTools::uuid()
        );

        $this->expectExceptionObject(DisbursementException::load(DisbursementException::DISBURSE_AMOUNT_CANNOT_BE_MINUS_ZERO));
        $this->disbursementGateway->disburse($disburseRequest);
    }

    public function badNumberDataProvider(): array
    {
        return [
            ["number"],
            [""],
            ["066304925m"],
            ["06 630 49 25"],
        ];
    }

    /**
     * @dataProvider badNumberDataProvider
     * @throws TokenCreationException
     * @throws Exception
     */
    public function test_disburse_GIVEN_bad_number_THEN_expected_exception(string $number)
    {
        $this->createToken();

        $disburseRequest = new DisburseRequestBody(
            1,
            $number,
            AbstractTools::uuid()
        );

        $this->expectExceptionCode(DisbursementException::DISBURSE_BAD_NUMBER);
        $this->disbursementGateway->disburse($disburseRequest);
    }

    /**
     * @throws Exception
     */
    public function test_disburse_reference()
    {
        $reference = $this->test_disburse_THEN_success();
        $disburseReference = $this->disbursementGateway->disburseReference($reference);

        $this->assertIsArray($disburseReference);
        foreach ([
            "amount",
            "currency",
            "externalId",
            "payee",
            "payerMessage",
            "payeeNote",
            "status",
            "reason",
                     ] as $key) {
            $this->assertArrayHasKey($key, $disburseReference);
        }
    }

    /**
     * @throws Exception
     */
    public function test_balance()
    {
        sleep(10);
        $balance = $this->createToken()->disbursementGateway->balance();

        $this->assertArrayHasKey("availableBalance", $balance);
        $this->assertArrayHasKey("currency", $balance);
        $this->assertEquals($this->disbursementGateway->getCurrency(), $balance["currency"]);
    }

    /**
     * @throws Exception
     */
    public function test_accountHolderActive()
    {
        $this->assertTrue(
            $this
                ->createToken()
                ->disbursementGateway
                ->isAccountIsActive("066304925")
        );
    }

    /**
     * @throws Exception
     */
    public function test_accountHolderBasicUserInfo()
    {
        $accountInfo = $this
            ->createToken()
            ->disbursementGateway
            ->getAccountBasicInfo("46733123452")
        ;

        $this->assertIsArray($accountInfo);
        foreach ([
             "name",
             "given_name",
             "family_name",
             "birthdate",
             "locale",
             "gender",
                     ] as $key) {
            $this->assertArrayHasKey($key, $accountInfo);
        }

        $accountInfo = [
            "given_name" => $accountInfo["given_name"] ?? null,
            "family_name" => $accountInfo["family_name"] ?? null,
            "name" => $accountInfo["name"] ?? null,
        ];
        $this->assertEquals([
            "given_name" => "Sand",
            "family_name" => "Box",
            "name" => "Sand Box",
        ], $accountInfo);
    }

}
