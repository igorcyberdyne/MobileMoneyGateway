<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Exception\AccountHolderException;
use Ekolotech\MoMoGateway\Exception\BalanceException;
use Ekolotech\MoMoGateway\Exception\CollectionException;
use Ekolotech\MoMoGateway\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Exception\TransactionReferenceException;

interface CollectionGatewayInterface
{
    /**
     * @param CollectRequestBody $collectRequestBody
     * @return bool
     * @throws CollectionException
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws RefreshAccessException
     */
    public function collect(CollectRequestBody $collectRequestBody): bool;

    /**
     * @param string $reference
     * @return array
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     * @throws RefreshAccessException
     * @throws CollectionException
     */
    public function collectReference(string $reference): array;

    /**
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