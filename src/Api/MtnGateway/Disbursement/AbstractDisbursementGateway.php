<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Api\Dependencies\HttpClient;
use Ekolotech\MoMoGateway\Api\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Api\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Api\Exception\BalanceException;
use Ekolotech\MoMoGateway\Api\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Api\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Api\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Api\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\Model\RequestMethod;
use Ekolotech\MoMoGateway\Api\MtnGateway\AbstractMtnApiGateway;
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
     * @throws TokenCreationException
     */
    public function disburse(DisburseRequestBody $disburseRequestBody): bool
    {
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
            $client = HttpClient::create(["headers" => $headers, "body" =>  json_encode($disburseBody)]);
            $response = $client->request(RequestMethod::POST, $this->getDisbursementUrl() . "/v1_0/transfer");

            if ($response->getStatusCode() != self::STATUS_ACCEPTED) {
                $response->toArray();

                return false;
            }

            return true;
        } catch (Throwable $t) {
            throw DisbursementException::load(DisbursementException::DISBURSE_NOT_PERFORM, previous: $t);
        }
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
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function disburseReference(string $reference): array
    {
        return $this->transactionReference($reference);
    }

    /**
     * @return array
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws BalanceException
     */
    public function balance(): array
    {
        return $this->accountBalance();
    }

    /**
     * @param string $number
     * @return bool
     * @throws AccountHolderException
     */
    public function isAccountIsActive(string $number): bool
    {
        return $this->accountHolderActive($number);
    }

    /**
     * @param string $number
     * @return array
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     */
    public function getAccountBasicInfo(string $number): array
    {
        return $this->accountHolderBasicUserInfo($number);
    }
}