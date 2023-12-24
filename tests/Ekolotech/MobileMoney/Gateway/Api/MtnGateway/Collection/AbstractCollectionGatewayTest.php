<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\Dto\CollectRequestBody;
use Ekolotech\MobileMoney\Gateway\Api\Exception\CollectionException;
use Ekolotech\MobileMoney\Gateway\Api\Exception\TokenCreationException;
use Ekolotech\MobileMoney\Gateway\Api\Helper\AbstractTools;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;


class TestCollectionGateway extends SandboxCollectionGateway
{
}


/**
 * Test of that class on sandbox environment
 * @see TestCollectionGateway
 * @see SandboxCollectionGateway
 * @see AbstractCollectionGateway
 */
class AbstractCollectionGatewayTest extends TestCase
{
    private AbstractCollectionGateway $collectionGateway;
    private string $apiUser;
    private static MtnAuthenticationProduct $authenticationProduct;

    protected function setUp(): void
    {
        parent::setUp();

        static::$authenticationProduct = new MtnAuthenticationProduct(
            "65a9c425-1d54-4a7b-b7d4-9c756f681920",
            "0672b80420244d9f9d39330b0811e1cd",
            "d57e01802dd3456fbfc6c2998dca2426",
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
    private function givenCollectGateway(MtnAuthenticationProduct $auth): static
    {
        $this->collectionGateway = new TestCollectionGateway($auth);

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
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createApiUser();
        $this->assertEquals($this->apiUser, $auth->getApiUser());

        $response = $this->collectionGateway->getApiUser();
        $this->assertIsArray($response);
        $this->assertEquals(
            [
                "providerCallbackHost" => "sandbox.momodeveloper.mtn.com",
                "targetEnvironment" => "sandbox"
            ],
            [
                "providerCallbackHost" => $this->collectionGateway->getProviderCallbackHost(),
                "targetEnvironment" => $this->collectionGateway->currentApiEnvName()
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
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createApiKey();

        $this->assertNotEmpty($apiKey);

        return $apiKey;
    }

    /**
     * @return AbstractCollectionGatewayTest
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
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createToken();

        $this->assertNotEmpty($mtnAccessToken);

        return $this;
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
            $this->givenCollectGateway($auth)->collectionGateway->getBaseApiUrl()
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
            ->givenCollectGateway($auth)
            ->collectionGateway
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
            ->givenCollectGateway($auth)
            ->collectionGateway
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
     * @throws CollectionException
     * @throws TokenCreationException
     */
    public function test_requestToPay_THEN_success()
    {
        $this->createToken();

        $collectRequest = new CollectRequestBody(
            1,
            "46733123452",
            AbstractTools::uuid()
        );

        $this->assertTrue($this->collectionGateway->collect($collectRequest));
    }

    /**
     * @throws CollectionException
     * @throws TokenCreationException
     */
    public function test_requestToPay_GIVEN_zero_as_amount_THEN_expected_exception()
    {
        $this->createToken();

        $collectRequest = new CollectRequestBody(
            0,
            "46733123452",
            AbstractTools::uuid()
        );

        $this->expectExceptionObject(CollectionException::load(CollectionException::REQUEST_TO_PAY_AMOUNT_CANNOT_BE_MINUS_ZERO));
        $this->collectionGateway->collect($collectRequest);
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
     * @throws CollectionException
     * @throws TokenCreationException
     */
    public function test_requestToPay_GIVEN_bad_number_THEN_expected_exception(string $number)
    {
        $this->createToken();

        $collectRequest = new CollectRequestBody(
            1,
            $number,
            AbstractTools::uuid()
        );

        $this->expectExceptionCode(CollectionException::REQUEST_TO_PAY_BAD_NUMBER);
        $this->collectionGateway->collect($collectRequest);
    }

}
