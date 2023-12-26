<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Disbursement;

use Ekolotech\MobileMoney\Gateway\Api\Dto\DisburseRequestBody;
use Ekolotech\MobileMoney\Gateway\Api\Exception\DisbursementException;
use Ekolotech\MobileMoney\Gateway\Api\Helper\AbstractTools;
use Ekolotech\MobileMoney\Gateway\Api\Model\RequestMethod;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\AbstractMtnApiGateway;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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


    public function getPayerMessage(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables(
            "Décaissement d'un montant de [[amount]] {$this->getCurrency()} au bénéfice du numéro [[number]]",
            $params
        );
    }


    public function getPayeeNote(): string
    {
        $args = func_get_args()[0] ?? [];

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Le compte au numéro [[number]] a été crédité de [[amount]] {$this->getCurrency()}", $params);
    }


    /**
     * @throws Exception
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
        }
        catch (Exception|TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw DisbursementException::load(DisbursementException::DISBURSE_NOT_PERFORM, previous: $e);
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
     * @throws Exception
     */
    public function disburseReference(string $reference): array
    {
        return $this->transactionReference($reference);
    }

    /**
     * @throws Exception
     */
    public function balance(): array
    {
        return $this->accountBalance();
    }

}