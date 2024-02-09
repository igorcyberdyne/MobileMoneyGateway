<?php

namespace Ekolotech\MoMoGateway\Tests\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Exception\EnvironmentException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\MtnAuthenticationProductException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Helper\LoggerImpl;
use Ekolotech\MoMoGateway\Interface\ApiGatewayLoggerInterface;
use Ekolotech\MoMoGateway\Model\Currency;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\AbstractDisbursementGateway;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;
use Ekolotech\MoMoGateway\Tests\MtnGateway\MtnAuthenticationProductConfig;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TestDisbursementGateway extends AbstractDisbursementGateway
    implements
    MtnApiEnvironmentConfigInterface,
    MtnApiAccessConfigListenerInterface,
    ApiGatewayLoggerInterface
{
    private LoggerImpl $logger;

    public function __construct(
        MtnAuthenticationProduct $authenticationProduct,
        ?MtnAccessToken          $mtnAccessToken = null
    )
    {
        $this->logger = new LoggerImpl();
        parent::__construct($authenticationProduct, $mtnAccessToken);
    }

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
    }

    public function onApiKeyCreated(string $apiKey): void
    {
        // TODO: Implement onApiUserCreated() method.
    }

    public function onTokenCreated(MtnAccessToken $mtnAccessToken): void
    {
        // TODO: Implement onApiUserCreated() method.
    }

    public function getLogger(): LoggerInterface|LoggerImpl
    {
        return $this->logger;
    }


}

class AbstractDisbursementGatewayTest extends TestCase
{

    private TestDisbursementGateway $disbursementGateway;
    private string $apiUser;
    private static MtnAuthenticationProduct $authenticationProduct;

    protected function setUp(): void
    {
        parent::setUp();
        static::$authenticationProduct = MtnAuthenticationProductConfig::disbursementKeys();
    }

    /**
     * @param string $expected
     * @param string $type
     * @return void
     */
    public function assertLoggerContains(string $expected, string $type = "info"): void
    {
        $loggerMessages = $this->disbursementGateway->getLogger()->getLoggerMessages()[$type] ?? [];
        $this->assertNotEmpty($loggerMessages);
        $this->assertContains($expected, $loggerMessages);
        //var_dump($loggerMessages);
    }

    private function givenApiUser(): string
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
    private function givenDisburseGateway(MtnAuthenticationProduct $auth): static
    {
        $this->disbursementGateway = new TestDisbursementGateway($auth);

        return $this;
    }


    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     * @throws MtnAccessKeyException
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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse api user]");
    }

    /**
     * @return string
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse api key]");

        return $apiKey;
    }

    /**
     * @return $this
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws TokenCreationException
     * @throws RefreshAccessException
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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse token]");

        return $this;
    }


    public static function urlDataProvider(): array
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
        $disbursementGateway = new TestDisbursementGateway($this->givenAuthenticationProduct());

        $this->assertEquals($data, $disbursementGateway->$method());
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
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

    public static function listenerDataProvider(): array
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
            $apiKey = $this->createApiKeyAssociateToApiUser();
            $apiUser = $this->apiUser;
        } else {
            $apiUser = $this->givenApiUser();
        }

        $auth = $this->givenAuthenticationProduct(
            apiUser: $apiUser,
            apiKey: $apiKey
        );

        $mock = $this->getMockBuilder(TestDisbursementGateway::class)
            ->setConstructorArgs([$auth])
            ->onlyMethods([$methodName])
            ->getMock();
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
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function test_create_apiUser_and_get_THEN_created()
    {
        $this->createApiUser();
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
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     */
    public function test_create_apiKey_WHITH_associate_to_apiUser_THEN_create()
    {
        $this->createApiKeyAssociateToApiUser();
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
     * @throws DisbursementException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function test_disburse_WITH_bad_reference_THEN_failed()
    {
        $this->createToken();

        $disburseRequest = new DisburseRequestBody(
            1,
            "46733123452",
            "bad-uuid",
        );

        $this->expectExceptionCode(DisbursementException::DISBURSE_BAD_REFERENCE_UUID);
        $this->assertTrue($this->disbursementGateway->disburse($disburseRequest));

        return $disburseRequest->reference;
    }

    /**
     * @return string
     * @throws DisbursementException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse]");

        return $disburseRequest->reference;
    }

    /**
     * @return void
     * @throws DisbursementException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
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

    public static function badNumberDataProvider(): array
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
     * @throws DisbursementException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
     * @return void
     * @throws DisbursementException
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse reference]");
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws BalanceException
     */
    public function test_balance()
    {
        sleep(10);
        $balance = $this->createToken()->disbursementGateway->balance();

        $this->assertArrayHasKey("availableBalance", $balance);
        $this->assertArrayHasKey("currency", $balance);
        $this->assertEquals($this->disbursementGateway->getCurrency(), $balance["currency"]);
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse balance]");
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws MtnAccessKeyException
     * @throws MtnAuthenticationProductException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws AccountHolderException
     */
    public function test_accountHolderActive()
    {
        $this->assertTrue(
            $this
                ->createToken()
                ->disbursementGateway
                ->isAccountIsActive("066304925")
        );
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse is account is active]");
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
            ->disbursementGateway
            ->getAccountBasicInfo("46733123452");

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
        $this->assertLoggerContains("[mobilemoney-gateway process] START <<<<<<<<<<< [disburse account holder basic info]");
    }

}
