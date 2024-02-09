<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;

interface DisbursementGatewayInterface
{
    /**
     * @param DisburseRequestBody $disburseRequestBody
     * @return bool
     * @throws DisbursementException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function disburse(DisburseRequestBody $disburseRequestBody): bool;

    /**
     * @param string $reference
     * @return array
     * @throws DisbursementException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function disburseReference(string $reference): array;

    /**
     * @return array
     * @throws BalanceException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function balance(): array;

    /**
     * @param string $number
     * @return bool
     * @throws AccountHolderException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function isAccountIsActive(string $number): bool;

    /**
     * @param string $number
     * @return array
     * @throws AccountHolderException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     */
    public function getAccountBasicInfo(string $number): array;
}