<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Dependencies\HttpClient;
use Ekolotech\MoMoGateway\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\CollectionException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Model\RequestMethod;
use Ekolotech\MoMoGateway\MtnGateway\AbstractMtnApiGateway;
use Throwable;

abstract class AbstractCollectionGateway extends AbstractMtnApiGateway implements CollectionGatewayInterface
{
    /**
     * @param CollectRequestBody $collectRequestBody
     * @return bool
     * @throws CollectionException
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws RefreshAccessException
     */
    public function collect(CollectRequestBody $collectRequestBody): bool
    {
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
            $client = HttpClient::create(["headers" => $headers, "body" => json_encode($collectBody)]);
            $response = $client->request(RequestMethod::POST, $this->getCollectionUrl() . "/v1_0/requesttopay");

            if ($response->getStatusCode() != self::STATUS_ACCEPTED) {
                $response->toArray();

                return false;
            }

            return true;
        } catch (Throwable $t) {
            throw CollectionException::load(CollectionException::REQUEST_TO_PAY_NOT_PERFORM, previous: $t);
        }
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
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     * @throws RefreshAccessException
     */
    public function collectReference(string $reference): array
    {
        return $this->transactionReference($reference);
    }


    /**
     * @throws TokenCreationException
     * @throws BalanceException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     */
    public function balance(): array
    {
        return $this->accountBalance();
    }

    /**
     * @param string $number
     * @return bool
     * @throws AccountHolderException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
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
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function getAccountBasicInfo(string $number): array
    {
        return $this->accountHolderBasicUserInfo($number);
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