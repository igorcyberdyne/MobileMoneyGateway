<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Api\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Api\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Api\Exception\BalanceException;
use Ekolotech\MoMoGateway\Api\Exception\CollectionException;
use Ekolotech\MoMoGateway\Api\Exception\EnvironmentException;
use Ekolotech\MoMoGateway\Api\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Api\Exception\MtnAuthenticationProductException;
use Ekolotech\MoMoGateway\Api\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Api\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Api\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\Model\Currency;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use PHPUnit\Framework\TestCase;


class TestCollectionGateway extends AbstractCollectionGateway implements MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
{
    public string $apiUserCreated = "";
    public function getBaseApiUrl(): string
    {
        return "https://sandbox.momodeveloper.mtn.com";
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
        $this->apiUserCreated = $apiUser;
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


/**
 * Test of that class on sandbox environment
 * @see TestCollectionGateway
 * @see AbstractCollectionGateway
 */
class AbstractCollectionGatewayTest extends TestCase
{
    private AbstractCollectionGateway|TestCollectionGateway $collectionGateway;
    private string $apiUser;
    private static MtnAuthenticationProduct $authenticationProduct;

    protected function setUp(): void
    {
        parent::setUp();

        static::$authenticationProduct = new MtnAuthenticationProduct(
            "0672b80420244d9f9d39330b0811e1cd",
            "d57e01802dd3456fbfc6c2998dca2426",
        );
    }

    /**
     * @return string
     */
    private function generateApiUser(): string
    {
        $this->apiUser = AbstractTools::uuid();

        return $this->apiUser;
    }

    /**
     * @param string|null $apiUser
     * @param string|null $apiKey
     * @return MtnAuthenticationProduct
     */
    private function givenAuthenticationProduct(
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            static::$authenticationProduct->getSubscriptionKeyOne(),
            static::$authenticationProduct->getSubscriptionKeyTwo(),
                $apiUser ?? static::$authenticationProduct->getApiUser(),
            $apiKey
        );
    }

    /**
     * @param MtnAuthenticationProduct $auth
     * @return $this
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     */
    private function givenCollectGateway(MtnAuthenticationProduct $auth): static
    {
        $this->collectionGateway = new TestCollectionGateway($auth);

        return $this;
    }


    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function givenApiUser(): void
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->generateApiUser(),
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
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function givenApiKey(): string
    {
        $this->givenApiUser();

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
     * @return $this
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function createToken(): static
    {
        $apiKey = $this->givenApiKey();

        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->apiUser,
            apiKey: $apiKey
        );

        $mtnAccessToken = $this
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createToken()
        ;

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
     * @param string $method
     * @param $data
     * @return void
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     */
    public function test_url_for_local_environment(string $method, $data)
    {
        $collectionGateway = new TestCollectionGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $collectionGateway->$method());
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     */
    public function test_baseUrl_and_productType()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->generateApiUser(),
            apiKey: "apiKey"
        );

        $this->assertEquals(
            "https://sandbox.momodeveloper.mtn.com",
            $this->givenCollectGateway($auth)->collectionGateway->getBaseApiUrl()
        );
    }

    public function listenerDataProvider(): array
    {
        return [
            "Listener for method onApiUserCreated" => [
                "methodName" => "onApiUserCreated",
            ],
            "Listener for method onApiKeyCreated" => [
                "methodName" => "onApiKeyCreated",
            ],
            "Listener for method onTokenCreated" => [
                "methodName" => "onTokenCreated",
            ],
        ];
    }

    /**
     * @dataProvider listenerDataProvider
     * @param $methodName
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_listener_on_method_THEN_methods_listen_is_called($methodName)
    {
        $apiKey = null;
        if ($methodName == "onTokenCreated") {
            $apiKey = $this->givenApiKey();
            $apiUser = $this->apiUser;
        } else {
            $apiUser = $this->generateApiUser();
        }

        $auth = $this->givenAuthenticationProduct(
            apiUser: $apiUser,
            apiKey: $apiKey
        );

        $mock = $this->getMockBuilder(TestCollectionGateway::class)
            ->setConstructorArgs([$auth])
            ->onlyMethods([$methodName])
            ->getMock()
        ;
        $mock->expects(self::exactly(1))->method($methodName);

        if ($methodName !== "onTokenCreated") {
            $mock->createApiUser();
            $mock->createApiKey();

            return;
        }

        $mock->createToken();
    }


    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     */
    public function test_createApiUser_WITHOUT_given_apiUserValue(): void
    {
        $auth = $this->givenAuthenticationProduct(apiUser: "");
        $this->assertEmpty($auth->getApiUser());

        $auth = $this
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->getAuthenticationProduct()
        ;

        $this->assertNotEmpty($auth->getApiUser());
        $this->assertEquals($auth->getApiUser(), $this->collectionGateway->apiUserCreated);
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function test_create_apiUser_and_get_THEN_created()
    {
        $this->givenApiUser();
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function test_create_apiKey_WHITHOUT_associate_to_apiUser_THEN_failed()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->generateApiUser(),
            apiKey: "apiKey"
        );

        $this->expectException(Exception::class);
        $this
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createApiKey();
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function test_create_apiKey_WHITH_associate_to_apiUser_THEN_create()
    {
        $this->givenApiKey();
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_create_token_THEN_failed()
    {
        $auth = $this->givenAuthenticationProduct(
            apiUser: $this->generateApiUser(),
            apiKey: "apiKey"
        );

        $this->expectException(TokenCreationException::class);
        $this
            ->givenCollectGateway($auth)
            ->collectionGateway
            ->createToken();
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_create_token_THEN_created()
    {
        $this->createToken();
    }

    /**
     * @return string
     * @throws CollectionException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_collect_THEN_success()
    {
        $this->createToken();

        $collectRequest = new CollectRequestBody(
            1,
            "46733123452",
            AbstractTools::uuid()
        );

        $this->assertTrue($this->collectionGateway->collect($collectRequest));
        
        return $collectRequest->reference;
    }

    /**
     * @return void
     * @throws CollectionException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_collect_GIVEN_zero_as_amount_THEN_expected_exception()
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
     * @param string $number
     * @return void
     * @throws CollectionException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_collect_GIVEN_bad_number_THEN_expected_exception(string $number)
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


    /**
     * @return void
     * @throws CollectionException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function test_collect_reference()
    {
        $reference = $this->test_collect_THEN_success();
        $collectReference = $this->collectionGateway->collectReference($reference);

        $this->assertIsArray($collectReference);
        foreach ([
                     "amount",
                     "currency",
                     "externalId",
                     "payer",
                     "payerMessage",
                     "payeeNote",
                     "status",
                     "reason",
                 ] as $key) {
            $this->assertArrayHasKey($key, $collectReference);
        }
    }

    /**
     * @return void
     * @throws BalanceException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_balance()
    {
        sleep(10);
        $balance = $this->createToken()->collectionGateway->balance();

        $this->assertArrayHasKey("availableBalance", $balance);
        $this->assertArrayHasKey("currency", $balance);
        $this->assertEquals($this->collectionGateway->getCurrency(), $balance["currency"]);
    }

    /**
     * @return void
     * @throws AccountHolderException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_accountHolderActive()
    {
        $this->assertTrue(
            $this
                ->createToken()
                ->collectionGateway
                ->isAccountIsActive("066304925")
        );
    }

    /**
     * @return void
     * @throws AccountHolderException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_accountHolderBasicUserInfo()
    {
        $accountInfo = $this
            ->createToken()
            ->collectionGateway
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
