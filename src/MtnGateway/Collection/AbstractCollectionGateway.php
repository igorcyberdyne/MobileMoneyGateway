<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Dependencies\AbstractHttpClient;
use Ekolotech\MoMoGateway\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\CollectionException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Model\RequestMethod;
use Ekolotech\MoMoGateway\MtnGateway\AbstractMtnApiGateway;
use Exception;
use Throwable;

abstract class AbstractCollectionGateway extends AbstractMtnApiGateway implements CollectionGatewayInterface
{
    /**
     * @param CollectRequestBody $collectRequestBody
     * @return bool
     * @throws CollectionException
     * @throws TokenCreationException
     * @throws RefreshAccessException
     */
    public function collect(CollectRequestBody $collectRequestBody): bool
    {
        return $this->processTracker->start(function () use ($collectRequestBody) {
            $collectBody = $this->validateCollectRequestBody($collectRequestBody);

            $headers = [
                'Authorization' => $this->buildBearerToken(),
                'X-Callback-Url' => $this->getProviderCallbackUrl(),
                'X-Reference-Id' => $collectRequestBody->reference,
                'X-Target-Environment' => $this->currentApiEnvName(),
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $this->authenticationProduct->getSubscriptionKeyOne(),
            ];

            if (!$this->isProd()) {
                unset($headers["X-Callback-Url"]);
            }

            try {
                $response = AbstractHttpClient::create(
                    [
                        "headers" => $headers,
                        "body" => json_encode($collectBody)
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::POST, $this->getCollectionUrl() . "/v1_0/requesttopay");

                if ($response->getStatusCode() == self::STATUS_ACCEPTED) {
                    return true;
                }

                if ($this instanceof MtnApiCollectionErrorListenerInterface) {
                    try {
                        $this->onCollectError($collectRequestBody->reference, $response->toArray(false));
                    } catch (Exception) {
                        // TODO something
                    }
                }

                return false;
            } catch (Throwable $t) {
                throw CollectionException::load(CollectionException::REQUEST_TO_PAY_NOT_PERFORM, previous: $t);
            }
        }, "collect");
    }

    /**
     * @param CollectRequestBody $collectRequestBody
     * @return array
     * @throws CollectionException
     */
    private function validateCollectRequestBody(CollectRequestBody $collectRequestBody): array
    {
        if (0 >= $collectRequestBody->amount) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_AMOUNT_CANNOT_BE_MINUS_ZERO);
        }

        if (1 !== preg_match('/^[0-9]+$/', $collectRequestBody->number)) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_BAD_NUMBER);
        }

        if (!AbstractTools::isUuid($collectRequestBody->reference)) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_BAD_REFERENCE_UUID);
        }

        return [
            "amount" => $collectRequestBody->amount,
            "currency" => $this->getCurrency(),
            "externalId" => $collectRequestBody->reference,
            "payer" => [
                "partyIdType" => self::MSISDN_ACCOUNT_TYPE,
                "partyId" => $collectRequestBody->number
            ],
            "payerMessage" => $this->getPayerMessage(["amount" => $collectRequestBody->amount, "number" => $collectRequestBody->number]),
            "payeeNote" => $this->getPayeeNote(["amount" => $collectRequestBody->amount, "number" => $collectRequestBody->number]),
        ];
    }

    protected function getPayerMessage(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Le compte au numéro [[number]] a été débité d'un montant de [[amount]] {$this->getCurrency()}", $params);
    }

    protected function getPayeeNote(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Montant de [[amount]] {$this->getCurrency()} collecté sur le numéro [[number]]", $params);
    }

    /**
     * @param string $reference
     * @return array
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     * @throws RefreshAccessException
     * @throws CollectionException
     */
    public function collectReference(string $reference): array
    {
        return $this->processTracker->start(function () use ($reference) {
            if (!AbstractTools::isUuid($reference)) {
                throw CollectionException::load(CollectionException::REQUEST_TO_PAY_BAD_REFERENCE_UUID);
            }

            return $this->transactionReference($reference);
        }, "collect reference");
    }


    /**
     * @throws TokenCreationException
     * @throws BalanceException
     * @throws RefreshAccessException
     */
    public function balance(): array
    {
        return $this->processTracker->start(function () {
            return $this->accountBalance();
        }, "collect balance");
    }

    /**
     * @param string $number
     * @return bool
     * @throws AccountHolderException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function isAccountIsActive(string $number): bool
    {
        return $this->processTracker->start(function () use ($number) {
            return $this->accountHolderActive($number);
        }, "collect is account is active");
    }

    /**
     * @param string $number
     * @return array
     * @throws AccountHolderException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function getAccountBasicInfo(string $number): array
    {
        return $this->processTracker->start(function () use ($number) {
            return $this->accountHolderBasicUserInfo($number);
        }, "collect account holder basic info");
    }

    protected function getTokenUrl(): string
    {
        return $this->getCollectionUrl() . "/token/";
    }

    protected function getCollectionUrl(): string
    {
        return $this->getBaseApiUrl() . "/collection";
    }

    protected function getTransactionReferenceUrl(): string
    {
        return $this->getCollectionUrl() . "/v1_0/requesttopay/{referenceId}";
    }

    protected function getAccountHolderUrl(): string
    {
        return $this->getCollectionUrl() . "/v1_0/accountholder/{accountHolderIdType}/{accountHolderId}/active";
    }

    protected function getAccountHolderBasicInfoUrl(): string
    {
        return $this->getCollectionUrl() . "/v1_0/accountholder/msisdn/{accountHolderMSISDN}/basicuserinfo";
    }

    protected function getAccountBalanceUrl(): string
    {
        return $this->getCollectionUrl() . "/v1_0/account/balance";
    }

}