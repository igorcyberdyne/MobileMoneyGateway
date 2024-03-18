<?php

namespace Ekolotech\MoMoGateway\MtnGateway;

use Ekolotech\MoMoGateway\Dependencies\AbstractHttpClient;
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
use Ekolotech\MoMoGateway\Helper\ProcessTracker;
use Ekolotech\MoMoGateway\Interface\ApiGatewayLoggerInterface;
use Ekolotech\MoMoGateway\Model\RequestMethod;
use Ekolotech\MoMoGateway\MtnGateway\Collection\AbstractCollectionGateway;
use Ekolotech\MoMoGateway\MtnGateway\Collection\MtnApiCollectionErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Disbursement\MtnApiDisbursementErrorListenerInterface;
use Ekolotech\MoMoGateway\MtnGateway\Interface\MtnApiAccessConfigErrorListenerInterface;
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
    protected ProcessTracker $processTracker;


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

        $this->processTracker = new ProcessTracker($this instanceof ApiGatewayLoggerInterface ? $this : null);

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
        return $this->processTracker->start(function () {
            try {
                $response = AbstractHttpClient::create(
                    [
                        "headers" => [
                            "Content-type" => "application/json",
                            "X-Reference-Id" => $this->authenticationProduct->getApiUser(),
                            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                        ],
                        "body" => '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}'
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::POST, $this->getCreateApiUserUrl());

                if (!in_array($response->getStatusCode(), [self::STATUS_CREATED, self::STATUS_CONFLICT])) {

                    if ($this instanceof MtnApiAccessConfigErrorListenerInterface) {
                        try {
                            $error = $response->toArray(false);
                            $this->processTracker->getApiGatewayLogger()?->getLogger()?->emergency(json_encode([
                                "envName" => $this->currentApiEnvName(),
                                "onApiUserCreationError" => $error,
                                "authenticationProduct" => $this->authenticationProduct->toArray(),
                            ]));
                            $this->onApiUserCreationError($this->authenticationProduct, $error);
                        } catch (Exception) {
                            // TODO something
                        }
                    }

                    throw new Exception("Cannot create API User");
                }

                if ($this instanceof MtnApiAccessConfigListenerInterface) {
                    try {
                        $this->onApiUserCreated($this->authenticationProduct->getApiUser());
                    } catch (Exception) {
                        // TODO something
                    }
                }

                return true;
            } catch (Throwable $t) {
                throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_USER, $t);
            }
        }, ($this instanceof AbstractCollectionGateway ? "collect" : "disburse") . " api user");
    }


    /**
     * @throws MtnAccessKeyException
     */
    public function getApiUser(): array
    {
        return $this->processTracker->start(function () {
            try {
                $response = AbstractHttpClient::create(
                    [
                        "headers" => [
                            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                        ],
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::GET, $this->getRetrieveApiUserUrl());

                if ($response->getStatusCode() !== self::STATUS_SUCCESS) {
                    throw new Exception("Cannot retrieve API User");
                }

                return $response->toArray();
            } catch (Throwable $t) {
                throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_RETRIEVE_API_USER, $t);
            }
        }, ($this instanceof AbstractCollectionGateway ? "collect" : "disburse") . " retrieve api user");
    }


    /**
     * @throws MtnAccessKeyException
     */
    public function createApiKey(): string
    {
        return $this->processTracker->start(function () {
            if ($this->isProd()) {
                throw MtnAccessKeyException::load(MtnAccessKeyException::CANNOT_CREATE_API_KEY_IN_PRODUCTION);
            }

            try {
                //$body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
                $body = "";
                $response = AbstractHttpClient::create(
                    [
                        "headers" => [
                            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                        ],
                        "body" => $body
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::POST, $this->getCreateApiKeyUrl());

                if ($response->getStatusCode() != self::STATUS_CREATED) {

                    if ($this instanceof MtnApiAccessConfigErrorListenerInterface) {
                        try {
                            $error = $response->toArray(false);
                            $this->processTracker->getApiGatewayLogger()?->getLogger()?->emergency(json_encode([
                                "envName" => $this->currentApiEnvName(),
                                "onApiKeyCreationError" => $error,
                                "authenticationProduct" => $this->authenticationProduct->toArray(),
                            ]));
                            $this->onApiKeyCreationError($this->authenticationProduct, $error);
                        } catch (Exception) {
                            // TODO something
                        }
                    }

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
        }, ($this instanceof AbstractCollectionGateway ? "collect" : "disburse") . " api key");
    }

    /**
     * @return MtnAccessToken
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function createToken(): MtnAccessToken
    {
        return $this->processTracker->start(function () {
            try {
                if (empty($this->authenticationProduct->getApiKey())) {
                    $this->authenticationProduct->setApiKey($this->createApiKey());
                }

                $headers = [
                    'Authorization' => 'Basic ' . AbstractTools::basicAuth($this->authenticationProduct->getApiUser(), $this->authenticationProduct->getApiKey()),
                    'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne()
                ];

                //$body = '{"providerCallbackHost": "' . $this->getProviderCallbackHost() . '"}';
                $body = "";
                $response = AbstractHttpClient::create(
                    [
                        "headers" => $headers,
                        "body" => $body
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::POST, $this->getTokenUrl());


                if ($response->getStatusCode() != self::STATUS_SUCCESS) {

                    if ($this instanceof MtnApiAccessConfigErrorListenerInterface) {
                        try {
                            $error = $response->toArray(false);
                            $this->processTracker->getApiGatewayLogger()?->getLogger()?->emergency(json_encode([
                                "envName" => $this->currentApiEnvName(),
                                "onTokenCreationError" => $error,
                                "authenticationProduct" => $this->authenticationProduct->toArray(),
                            ]));
                            $this->onTokenCreationError($this->authenticationProduct, $error);
                        } catch (Exception) {

                        }
                    }

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
        }, ($this instanceof AbstractCollectionGateway ? "collect" : "disburse") . " token");
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
     * @return string
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    protected function transactionReference(string $reference): array
    {
        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        try {
            $response = AbstractHttpClient::create(
                [
                    "headers" => $headers,
                ],
                apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
            )
                ->request(RequestMethod::GET, str_replace("{referenceId}", $reference, $this->getTransactionReferenceUrl()));

            if ($response->getStatusCode() == self::STATUS_SUCCESS) {
                return $response->toArray();
            }


            if ($this instanceof MtnApiCollectionErrorListenerInterface || $this instanceof MtnApiDisbursementErrorListenerInterface) {

                /**
                 * @see MtnApiCollectionErrorListenerInterface::onCollectReferenceError
                 * @see MtnApiDisbursementErrorListenerInterface::onDisburseReferenceError
                 */
                $listenerMethod = $this instanceof MtnApiCollectionErrorListenerInterface ? "onCollectReferenceError" : "onDisburseReferenceError";
                try {
                    $error = $response->toArray(false);
                    $this->processTracker->getApiGatewayLogger()?->getLogger()?->emergency(json_encode([
                        "envName" => $this->currentApiEnvName(),
                        "reference" => $reference,
                        $listenerMethod => $error,
                    ]));
                    $this->$listenerMethod($reference, $error);
                } catch (Exception) {
                    // TODO something
                }
            }

            throw TransactionReferenceException::load(TransactionReferenceException::TRANSACTION_REFERENCE_CANNOT_BE_RETRIEVE);
        } catch (Throwable $t) {
            throw TransactionReferenceException::load(TransactionReferenceException::TRANSACTION_REFERENCE_REQUEST_ERROR, previous: $t);
        }
    }

    /**
     * @param string $phoneNumber
     * @return bool
     * @throws AccountHolderException
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

        try {
            $url = str_replace(
                ['{accountHolderIdType}', '{accountHolderId}'],
                [strtolower(self::MSISDN_ACCOUNT_TYPE), $phoneNumber],
                $this->getAccountHolderUrl()
            );
            $response = AbstractHttpClient::create(
                [
                    "headers" => $headers,
                ],
                apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
            )
                ->request(RequestMethod::GET, $url);

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
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
            $response = AbstractHttpClient::create(
                [
                    "headers" => $headers,
                ],
                apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
            )
                ->request(RequestMethod::GET, $url);

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BASIC_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray();
        } catch (Throwable $t) {
            throw AccountHolderException::load(AccountHolderException::ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR, previous: $t);
        }
    }

    /**
     * @return array
     * @throws BalanceException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    protected function accountBalance(): array
    {
        $headers = [
            'Authorization' => $this->buildBearerToken(),
            'X-Target-Environment' => $this->currentApiEnvName(),
            'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
        ];

        try {
            $response = AbstractHttpClient::create([
                "headers" => $headers,
            ],
                apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
            )
                ->request(RequestMethod::GET, $this->getAccountBalanceUrl());

            if ($response->getStatusCode() != self::STATUS_SUCCESS) {
                throw BalanceException::load(BalanceException::BALANCE_CANNOT_BE_RETRIEVE);
            }

            return $response->toArray();
        } catch (Throwable $t) {
            throw BalanceException::load(BalanceException::BALANCE_REQUEST_ERROR, previous: $t);
        }
    }


    /**
     * @return string
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    protected function buildBearerToken(): string
    {
        return "Bearer {$this->getToken()}";
    }

}