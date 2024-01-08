<?php

namespace Ekolotech\MoMoGateway\MtnGateway;

use Ekolotech\MoMoGateway\Dependencies\HttpClient;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\EnvironmentException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\MtnAuthenticationProductException;
use Ekolotech\MoMoGateway\Exception\ProductTokenSessionException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Model\RequestMethod;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiEnvironmentConfigInterface;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;
use Exception;
use Throwable;

abstract class AbstractMtnApiGateway implements MtnApiAccessConfigInterface
{
    const STATUS_SUCCESS = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_CONFLICT = 409;
    const MSISDN_ACCOUNT_TYPE = "MSISDN";
    private bool $isCreateAccessAlreadyUsed = false;


    /**
     * @throws EnvironmentException
     * @throws MtnAuthenticationProductException
     */
    public function __construct(
        protected MtnAuthenticationProduct $authenticationProduct,
        protected ?MtnAccessToken          $mtnAccessToken = null,
    )
    {
        if (empty($this->authenticationProduct)) {
            throw MtnAuthenticationProductException::load(MtnAuthenticationProductException::PRODUCT_MUST_BE_CONFIGURED);
        }

        if (empty($this->authenticationProduct->getSubscriptionKeyOne())) {
            throw MtnAuthenticationProductException::load(MtnAuthenticationProductException::API_KEY_CANNOT_BE_EMPTY);
        }

        if (empty($this->authenticationProduct->getApiUser())) {
            $this->authenticationProduct->setApiUser(AbstractTools::uuid());

            try {
                $this->createApiUser();
            } catch (Throwable $throwable) {
                $this->authenticationProduct->setApiUser(null);
                throw MtnAuthenticationProductException::load(MtnAuthenticationProductException::API_USER_CANNOT_BE_EMPTY, previous: $throwable);
            }
        }

        if (!$this instanceof MtnApiEnvironmentConfigInterface) {
            throw EnvironmentException::load(EnvironmentException::MTN_ENV_NOT_CONFIGURED);
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


    /**
     * @throws MtnAccessKeyException
     */
    public function createApiUser(): bool
    {
        try {
            $client = HttpClient::create([
                "headers" => [
                    "Content-type" => "application/json",
                    "X-Reference-Id" => $this->authenticationProduct->getApiUser(),
                    'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                ],
                "body" => '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}'
            ]);

            $response = $client->request(RequestMethod::POST, $this->getCreateApiUserUrl());
            if (!in_array($response->getStatusCode(), [self::STATUS_CREATED, self::STATUS_CONFLICT])) {
                $response->toArray();

                throw new Exception("Cannot create API User");
            }

            if ($this instanceof MtnApiAccessConfigListenerInterface) {
                try {
                    $this->onApiUserCreated($this->authenticationProduct->getApiUser());
                } catch (Exception $exception) {
                    // TODO something
                }
            }

            return true;
        } catch (Throwable $t) {
            throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_USER, $t);
        }
    }


    /**
     * @throws MtnAccessKeyException
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
        } catch (Throwable $t) {
            throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_RETRIEVE_API_USER, $t);
        }
    }


    /**
     * @throws MtnAccessKeyException
     */
    public function createApiKey(): string
    {
        try {
            //$body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
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

            if ($this instanceof MtnApiAccessConfigListenerInterface) {
                try {
                    $this->onApiKeyCreated($apiKey);
                } catch (Exception) {

                }
            }

            return $apiKey;
        } catch (Throwable $t) {
            throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_KEY, $t);
        }
    }

    /**
     * @throws TokenCreationException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     */
    public function createToken(): MtnAccessToken
    {
        if (empty($this->authenticationProduct->getApiKey())) {

            if ($this->isProd()) {
                throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_CREATE_API_KEY_IN_PRODUCTION);
            }

            $this->authenticationProduct->setApiKey($this->createApiKey());
        }

        $headers = [
            'Authorization' => 'Basic ' . AbstractTools::basicAuth($this->authenticationProduct->getApiUser(), $this->authenticationProduct->getApiKey()),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
        ];

        //$body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
        $body = "";

        try {
            $client = HttpClient::create(["headers" => $headers, "body" => $body]);
            $response = $client->request(RequestMethod::POST, $this->getTokenUrl());

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                $response->toArray();

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
                } catch (Exception) {

                }
            }

            return $mtnAccessToken;
        } catch (Throwable $t) {
            if ($mtnAccessToken = $this->createAccess($t)) {
                return $mtnAccessToken;
            }

            throw TokenCreationException::load(TokenCreationException::TOKEN_CREATION_ERROR, previous: $t);
        }
    }

    /**
     * Help method to create apiUser, apiKey and token
     * @param Throwable $originError
     * @return MtnAccessToken|null
     * @throws RefreshAccessException
     */
    private function createAccess(Throwable $originError): ?MtnAccessToken
    {
        if (self::STATUS_UNAUTHORIZED == $originError->getCode() && $this->isCreateAccessAlreadyUsed) {
            return null;
        }

        $this->isCreateAccessAlreadyUsed = true;

        if ($this instanceof MtnApiEnvironmentConfigInterface && $this->isProd()) {
            return null;
        }

        $oldApiUser = $this->authenticationProduct->getApiUser();

        try {
            $this->authenticationProduct->setApiUser(AbstractTools::uuid());
            $this->createApiUser();

            $this->authenticationProduct->setApiKey($this->createApiKey());

            return $this->createToken();
        } catch (Throwable $t) {
            if ($t->getCode() === MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_USER) {
                $this->authenticationProduct->setApiUser($oldApiUser);
            }

            throw RefreshAccessException::load(
                RefreshAccessException::REFRESH_ACCESS_ERROR,
                [
                    "code" => $originError->getCode(),
                    "message" => $originError->getMessage(),
                ],
                $t
            );
        }
    }

    /**
     * @throws TokenCreationException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
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
     *
     * @param string $reference
     * @return array
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     * @throws RefreshAccessException
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
        } catch (Throwable $t) {
            throw TransactionReferenceException::load(TransactionReferenceException::TRANSACTION_REFERENCE_REQUEST_ERROR, previous: $t);
        }
    }

    /**
     * @param string $phoneNumber
     * @return bool
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
        } catch (Throwable $t) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_REQUEST_ERROR, previous: $t);
        }
    }


    /**
     * @param string $phoneNumber
     * @return array
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws TokenCreationException|RefreshAccessException
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
        } catch (Throwable $t) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR, previous: $t);
        }
    }

    /**
     * @throws TokenCreationException
     * @throws BalanceException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
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
        } catch (Throwable $t) {
            throw BalanceException::load(BalanceException::BALANCE_REQUEST_ERROR, previous: $t);
        }
    }


    /**
     * @throws TokenCreationException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     */
    protected function buildBearerToken(): string
    {
        return "Bearer {$this->getToken()}";
    }

}