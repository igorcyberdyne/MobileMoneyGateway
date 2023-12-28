<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway;

use Ekolotech\MobileMoney\Gateway\Api\Exception\AccountHolderException;
use Ekolotech\MobileMoney\Gateway\Api\Exception\BalanceException;
use Ekolotech\MobileMoney\Gateway\Api\Exception\ProductTokenSessionException;
use Ekolotech\MobileMoney\Gateway\Api\Exception\TokenCreationException;
use Ekolotech\MobileMoney\Gateway\Api\Exception\TransactionReferenceException;
use Ekolotech\MobileMoney\Gateway\Api\Helper\AbstractTools;
use Ekolotech\MobileMoney\Gateway\Api\Model\RequestMethod;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Interface\MtnApiAccessConfigInterface;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractMtnApiGateway implements MtnApiAccessConfigInterface
{
    const STATUS_SUCCESS = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_CONFLICT = 409;
    const MSISDN_ACCOUNT_TYPE = "MSISDN";


    /**
     * @throws Exception
     */
    public function __construct(
        protected MtnAuthenticationProduct $authenticationProduct,
        protected ?MtnAccessToken          $mtnAccessToken = null,
    )
    {
        if (empty($this->authenticationProduct)) {
            throw new Exception("Authentication Product must be configured");
        }

        if (empty($this->authenticationProduct->getSubscriptionKeyOne())) {
            throw new Exception("Authentication apiKey cannot be empty");
        }

        if (empty($this->authenticationProduct->getApiUser())) {
            throw new Exception("Authentication apiUser cannot be empty");
        }
    }

    /**
     * @return MtnAuthenticationProduct
     */
    public function getAuthenticationProduct(): MtnAuthenticationProduct
    {
        return $this->authenticationProduct;
    }

    /**
     * @return MtnAccessToken|null
     */
    public function getMtnAccessToken(): ?MtnAccessToken
    {
        return $this->mtnAccessToken;
    }

    public function getBaseApiUrl(): string
    {
        return $this->isProd() ? "https://proxy.momoapi.mtn.com" : "https://sandbox.momodeveloper.mtn.com";
    }

    public function currentApiEnvName(): string
    {
        return $this->isProd() ? "mtncongo" : "sandbox";
    }

    private function getCreateApiUserUrl(): string
    {
        return $this->getBaseApiUrl() . "/v1_0/apiuser";
    }

    private function getRetrieveApiUserUrl(): string
    {
        return $this->getBaseApiUrl() . "/v1_0/apiuser/{$this->authenticationProduct->getApiUser()}";
    }

    private function getCreateApiKeyUrl(): string
    {
        return $this->getBaseApiUrl() . "/v1_0/apiuser/{$this->authenticationProduct->getApiUser()}/apikey";
    }


    # ---------------- Url abstract method for the gateway operations  ---------------- #

    abstract protected function getTokenUrl(): string;

    abstract protected function getTransactionReferenceUrl(): string;

    abstract protected function getAccountHolderUrl(): string;

    abstract protected function getAccountHolderBasicInfoUrl(): string;

    abstract protected function getAccountBalanceUrl(): string;


    # ---------------- Config transaction message ---------------- #

    /**
     * @return string
     *
     * <p>You can provide an <b>array('key' => 'value')</b> in argument. <br/>
     * The <b>key</b> of that array correspond to a variable.
     * The available variables are :
     * </p><table>
     *
     * <thead>
     * <tr>
     * <th>Variable</th>
     * <th>Meaning</th>
     * </tr>
     * </thead>
     *
     * <tbody class="tbody">
     * <tr>
     * <td><b>number</b></td>
     * <td>The client phone number</td>
     * </tr>
     *
     * <tr>
     * <td><b>amount</b></td>
     * <td>The amount of transaction</td>
     * </tr>
     */
    abstract protected function getPayerMessage(): string;


    /**
     * @return string
     *
     * <p>You can provide an <b>array('key' => 'value')</b> in argument. <br/>
     * The <b>key</b> of that array correspond to a variable.
     * The available variables are :
     * </p><table>
     *
     * <thead>
     * <tr>
     * <th>Variable</th>
     * <th>Meaning</th>
     * </tr>
     * </thead>
     *
     * <tbody class="tbody">
     * <tr>
     * <td><b>number</b></td>
     * <td>The client phone number</td>
     * </tr>
     *
     * <tr>
     * <td><b>amount</b></td>
     * <td>The amount of transaction</td>
     * </tr>
     */
    abstract protected function getPayeeNote(): string;

    # ---------------- Config abstract method ---------------- #
    abstract public function getProviderCallbackUrl(): string;

    abstract public function getProviderCallbackHost(): string;

    abstract public function isProd(): bool;

    abstract public function getCurrency(): string;


    /**
     * @throws Exception
     */
    public function createApiUser(): bool
    {
        $headers = [
            "Content-type" => "application/json",
            "X-Reference-Id" => $this->authenticationProduct->getApiUser(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
        ];

        try {
            $body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
            $client = HttpClient::create([
                "headers" => $headers,
                "body" => $body
            ]);

            $response = $client->request(RequestMethod::POST, $this->getCreateApiUserUrl());
            if (!in_array($response->getStatusCode(), [self::STATUS_CREATED, self::STATUS_CONFLICT])) {
                $response->toArray();

                throw new Exception("Cannot create API User");
            }

            if ($this instanceof MtnApiAccessConfigListenerInterface) {
                try {
                    $this->onApiUserCreated();
                } catch (Exception $exception) {
                    // TODO something
                }
            }

            return true;
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface|Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    public function getApiUser(): array
    {
        try {
            $client = HttpClient::create([
                "headers" => [
                    'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                ],
            ]);

            $response = $client->request(RequestMethod::GET, $this->getRetrieveApiUserUrl());
            if ($response->getStatusCode() !== self::STATUS_SUCCESS) {
                throw new Exception("Cannot retrieve API User");
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface|Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    public function createApiKey(): string
    {
        try {
            $body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
            $body = "";

            $client = HttpClient::create([
                "headers" => [
                    'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                ],
                "body" => $body
            ]);

            $response = $client->request(RequestMethod::POST, $this->getCreateApiKeyUrl());
            if ($response->getStatusCode() != self::STATUS_CREATED) {
                $response->toArray();

                throw new Exception("Cannot create API Key");
            }

            $apiKey = $response->toArray()["apiKey"] ?? null;

            if (empty($apiKey)) {
                throw new Exception("Server error when creating apiKey");
            }

            // TODO dispatch event apiKey created
            if ($this instanceof MtnApiAccessConfigListenerInterface) {
                try {
                    $this->onApiKeyCreated($apiKey);
                } catch (Exception $exception) {
                    // TODO something
                }
            }

            return $apiKey;
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface|Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    public function createToken(): MtnAccessToken
    {
        if (empty($this->authenticationProduct->getApiKey())) {

            if ($this->isProd()) {
                throw new Exception("Api key not configured in production");
            }

            $this->authenticationProduct->setApiKey($this->createApiKey());
        }

        $headers = [
            'Authorization' => 'Basic ' . AbstractTools::basicAuth($this->authenticationProduct->getApiUser(), $this->authenticationProduct->getApiKey()),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
        ];

        $body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
        $body = "";

        try {
            $client = HttpClient::create(["headers" => $headers, "body" => $body]);
            $response = $client->request(RequestMethod::POST, $this->getTokenUrl());

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                throw ProductTokenSessionException::load(ProductTokenSessionException::PRODUCT_TOKEN_SESSION_CANNOT_BE_CREATE);
            }

            $tokeData = $response->toArray();

            $mtnAccessToken = new MtnAccessToken(
                $tokeData["access_token"],
                $tokeData["token_type"],
                $tokeData["expires_in"],
            );

            if ($this instanceof MtnApiAccessConfigListenerInterface) {
                try {
                    $this->onTokenCreated($mtnAccessToken);
                } catch (Exception $exception) {
                    // TODO something
                }
            }

            return $mtnAccessToken;
        } catch (TransportExceptionInterface|DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|Exception $e) {
            throw TokenCreationException::load(TokenCreationException::TOKEN_CREATION_ERROR, previous: $e);
        }
    }

    public function createAccess(): array
    {
        return [];
    }

    /**
     * @throws TokenCreationException
     * @throws Exception
     */
    private function getToken(): string
    {
        if (empty($this->mtnAccessToken) || $this->mtnAccessToken->isExpired()) {
            $this->mtnAccessToken = $this->createToken();
        }

        return $this->mtnAccessToken->getAccessToken();
    }

    /**
     * Retrieve reference for collection or disbursement
     * @param string $reference
     * @return array
     * @throws TransactionReferenceException
     * @throws Exception
     */
    protected function transactionReference(string $reference): array
    {
        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        try {
            $url = str_replace("{referenceId}", $reference, $this->getTransactionReferenceUrl());

            $client = HttpClient::create(["headers" => $headers]);
            $response = $client->request(RequestMethod::GET, $url);

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                throw TransactionReferenceException::load(TransactionReferenceException::TRANSACTION_REFERENCE_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|Exception $e) {
            throw TransactionReferenceException::load(TransactionReferenceException::TRANSACTION_REFERENCE_REQUEST_ERROR, previous: $e);
        }
    }

    /**
     * @throws AccountHolderException
     * @throws Exception
     */
    protected function accountHolderActive(string $phoneNumber): bool
    {
        if (1 !== preg_match('/^[0-9]+$/', $phoneNumber)) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BAD_NUMBER);
        }

        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        $url = str_replace(['{accountHolderIdType}', '{accountHolderId}'], [strtolower(self::MSISDN_ACCOUNT_TYPE), $phoneNumber], $this->getAccountHolderUrl());

        try {

            $client = HttpClient::create(["headers" => $headers]);
            $response = $client->request(RequestMethod::GET, $url);

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                $response->toArray();

                throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray()["result"] === true;
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|Exception $e) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_REQUEST_ERROR, previous: $e);
        }

    }

    /**
     * @throws AccountHolderException
     * @throws Exception
     */
    protected function accountHolderBasicUserInfo(string $phoneNumber): array
    {
        if (1 !== preg_match('/^[0-9]+$/', $phoneNumber)) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BAD_NUMBER);
        }

        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        $url = str_replace("{accountHolderMSISDN}", $phoneNumber, $this->getAccountHolderBasicInfoUrl());

        try {

            $client = HttpClient::create(["headers" => $headers]);
            $response = $client->request(RequestMethod::GET, $url);

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BASIC_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|Exception $e) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR, previous: $e);
        }
    }

    /**
     * @throws Exception
     */
    protected function accountBalance(): array
    {
        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        try {
            $client = HttpClient::create(["headers" => $headers]);
            $response = $client->request(RequestMethod::GET, $this->getAccountBalanceUrl());

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                $response->toArray();

                throw BalanceException::load(BalanceException::BALANCE_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|Exception $e) {
            throw BalanceException::load(BalanceException::BALANCE_REQUEST_ERROR, previous: $e);
        }
    }

    /**
     * @throws Exception
     */
    protected function buildBearerToken(): string
    {
        return "Bearer {$this->getToken()}";
    }

}