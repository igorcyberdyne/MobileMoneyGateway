<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Dependencies\AbstractHttpClient;
use Ekolotech\MoMoGateway\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Model\RequestMethod;
use Ekolotech\MoMoGateway\MtnGateway\AbstractMtnApiGateway;
use Exception;
use Throwable;

abstract class AbstractDisbursementGateway extends AbstractMtnApiGateway implements DisbursementGatewayInterface
{
    protected function getDisbursementUrl(): string
    {
        return $this->getBaseApiUrl() . "/disbursement";
    }

    protected function getTokenUrl(): string
    {
        return $this->getDisbursementUrl() . "/token/";
    }

    protected function getTransactionReferenceUrl(): string
    {
        return $this->getDisbursementUrl() . "/v1_0/transfer/{referenceId}";
    }

    protected function getAccountHolderUrl(): string
    {
        return $this->getDisbursementUrl() . "/v1_0/accountholder/{accountHolderIdType}/{accountHolderId}/active";
    }

    protected function getAccountHolderBasicInfoUrl(): string
    {
        return $this->getDisbursementUrl() . "/v1_0/accountholder/msisdn/{accountHolderMSISDN}/basicuserinfo";
    }

    protected function getAccountBalanceUrl(): string
    {
        return $this->getDisbursementUrl() . "/v1_0/account/balance";
    }


    protected function getPayerMessage(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables(
            "Décaissement d'un montant de [[amount]] {$this->getCurrency()} au bénéfice du numéro [[number]]",
            $params
        );
    }


    protected function getPayeeNote(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Le compte au numéro [[number]] a été crédité de [[amount]] {$this->getCurrency()}", $params);
    }


    /**
     * @param DisburseRequestBody $disburseRequestBody
     * @return bool
     * @throws DisbursementException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function disburse(DisburseRequestBody $disburseRequestBody): bool
    {
        return $this->processTracker->start(function () use ($disburseRequestBody) {
            $disburseBody = $this->validateDisburseRequestBody($disburseRequestBody);

            $headers = [
                'Authorization' => $this->buildBearerToken(),
                'X-Callback-Url' => $this->getProviderCallbackUrl(),
                'X-Reference-Id' => $disburseRequestBody->reference,
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
                        "body" => json_encode($disburseBody)
                    ],
                    apiGatewayLogger: $this->processTracker->getApiGatewayLogger()
                )
                    ->request(RequestMethod::POST, $this->getDisbursementUrl() . "/v1_0/transfer");

                if ($response->getStatusCode() == self::STATUS_ACCEPTED) {
                    return true;
                }

                if ($this instanceof MtnApiDisbursementErrorListenerInterface) {
                    try {
                        $this->onDisburseError($disburseRequestBody->reference, $response->toArray(false));
                    } catch (Exception) {
                        // TODO something
                    }
                }

                return false;
            } catch (Throwable $t) {
                throw DisbursementException::load(DisbursementException::DISBURSE_NOT_PERFORM, previous: $t);
            }
        }, "disburse");
    }


    /**
     * @param DisburseRequestBody $disburseRequestBody
     * @return array
     * @throws DisbursementException
     */
    private function validateDisburseRequestBody(DisburseRequestBody $disburseRequestBody): array
    {
        if (0 >= $disburseRequestBody->amount) {
            throw DisbursementException::load(DisbursementException::DISBURSE_AMOUNT_CANNOT_BE_MINUS_ZERO);
        }

        if (1 !== preg_match('/^[0-9]+$/', $disburseRequestBody->number)) {
            throw DisbursementException::load(DisbursementException::DISBURSE_BAD_NUMBER);
        }

        if (!AbstractTools::isUuid($disburseRequestBody->reference)) {
            throw DisbursementException::load(DisbursementException::DISBURSE_BAD_REFERENCE_UUID);
        }

        return [
            "amount" => $disburseRequestBody->amount,
            "currency" => $this->getCurrency(),
            "externalId" => $disburseRequestBody->reference,
            "payee" => [
                "partyIdType" => self::MSISDN_ACCOUNT_TYPE,
                "partyId" => $disburseRequestBody->number
            ],
            "payerMessage" => $this->getPayerMessage(["amount" => $disburseRequestBody->amount, "number" => $disburseRequestBody->number]),
            "payeeNote" => $this->getPayeeNote(["amount" => $disburseRequestBody->amount, "number" => $disburseRequestBody->number]),
        ];
    }

    /**
     * @param string $reference
     * @return array
     * @throws DisbursementException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function disburseReference(string $reference): array
    {
        return $this->processTracker->start(function () use ($reference) {
            if (!AbstractTools::isUuid($reference)) {
                throw DisbursementException::load(DisbursementException::DISBURSE_BAD_REFERENCE_UUID);
            }

            return $this->transactionReference($reference);
        }, "disburse reference");
    }

    /**
     * @return array
     * @throws BalanceException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function balance(): array
    {
        return $this->processTracker->start(function () {
            return $this->accountBalance();
        }, "disburse balance");
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
        }, "disburse is account is active");
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
        }, "disburse account holder basic info");
    }
}