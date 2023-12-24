<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\Dto\CollectRequestBody;
use Ekolotech\MobileMoney\Gateway\Api\Exception\CollectionException;
use Ekolotech\MobileMoney\Gateway\Api\Helper\AbstractTools;
use Ekolotech\MobileMoney\Gateway\Api\Interface\CollectionGatewayInterface;
use Ekolotech\MobileMoney\Gateway\Api\Model\RequestMethod;
use Ekolotech\MobileMoney\Gateway\Api\MtnGateway\AbstractMtnApiGateway;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractCollectionGateway extends AbstractMtnApiGateway implements CollectionGatewayInterface
{
    protected function getCollectionUrl(): string
    {
        return $this->getBaseApiUrl() . "/collection";
    }

    protected function getTokenUrl(): string
    {
        return $this->getCollectionUrl() . "/token/";
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


    public function getPayerMessage(): string
    {
        $args = func_get_args();

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Votre compte au numéro[[number]] a été débité d'un montant[[amount]]", $params);
    }


    public function getPayeeNote(): string
    {
        $args = func_get_args();

        $params["number"] = $args["number"] ?? "";
        $params["amount"] = $args["amount"] ?? "";

        return AbstractTools::injectVariables("Votre compte au numéro[[number]] a été débité de[[amount]]", $params);
    }


    /**
     * @throws CollectionException
     * @throws Exception
     */
    public function collect(CollectRequestBody $collectRequestBody): bool
    {
        $this->validateCollectRequestBody($collectRequestBody);

        $payBody = [
            "amount" => $collectRequestBody->amount,
            "currency" => $this->getCurrency(),
            "externalId" => $collectRequestBody->reference,
            "payer" => [
                "partyIdType" => self::MSISDN_ACCOUNT_TYPE,
                "partyId" => $collectRequestBody->number
            ],
            "payerMessage" => $this->getPayerMessage(),
            "payeeNote" => $this->getPayeeNote(),
        ];

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
            $client = HttpClient::create(["headers" => $headers, "body" =>  json_encode($payBody)]);
            $response = $client->request(RequestMethod::POST, $this->getCollectionUrl() . "/v1_0/requesttopay");

            if ($response->getStatusCode() != self::STATUS_ACCEPTED) {
                $response->toArray();

                return false;
            }

            return true;
        }
        catch (Exception|TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_NOT_PERFORM, previous: $e);
        }
    }


    /**
     * @param CollectRequestBody $collectRequestBody
     * @return void
     * @throws CollectionException
     */
    private function validateCollectRequestBody(CollectRequestBody $collectRequestBody): void
    {
        if (0 >= $collectRequestBody->amount) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_AMOUNT_CANNOT_BE_MINUS_ZERO);
        }

        if (1 !== preg_match('/^[0-9]+$/', $collectRequestBody->number)) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_BAD_NUMBER);
        }
    }

    /**
     * @throws Exception
     */
    public function collectReference(string $reference): array
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