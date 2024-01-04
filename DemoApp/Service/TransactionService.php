<?php

namespace DemoApp\Service;

use DemoApp\RepositoryImpl\InMemoryCollectionAccessRepository;
use DemoApp\RepositoryImpl\InMemoryDisbursementAccessRepository;
use DemoApp\Service\ByFactory\CollectionGatewayServiceImpl;
use DemoApp\Service\ByFactory\DisbursementGatewayServiceImpl;
use DemoApp\Service\ByInherit\CollectionGatewayService;
use DemoApp\Service\ByInherit\DisbursementGatewayService;
use Ekolotech\MoMoGateway\Api\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Api\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Api\Exception\BalanceException;
use Ekolotech\MoMoGateway\Api\Exception\CollectionException;
use Ekolotech\MoMoGateway\Api\Exception\DisbursementException;
use Ekolotech\MoMoGateway\Api\Exception\EnvironmentException;
use Ekolotech\MoMoGateway\Api\Exception\FactoryException;
use Ekolotech\MoMoGateway\Api\Exception\MtnAccessKeyException;
use Ekolotech\MoMoGateway\Api\Exception\MtnAuthenticationProductException;
use Ekolotech\MoMoGateway\Api\Exception\RefreshAccessException;
use Ekolotech\MoMoGateway\Api\Exception\TokenCreationException;
use Ekolotech\MoMoGateway\Api\Exception\TransactionReferenceException;
use Ekolotech\MoMoGateway\Api\Factory\ApiGatewayFactory;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Exception;

final class TransactionService
{
    private CollectionGatewayInterface $collectionGateway;
    private DisbursementGatewayInterface $disbursementGateway;

    /**
     * @param bool $useFactory
     * @throws EnvironmentException
     * @throws FactoryException
     * @throws MtnAuthenticationProductException
     */
    public function __construct(bool $useFactory = true)
    {
        if ($useFactory) {
            $this->collectionGateway = ApiGatewayFactory::loadMtnCollectionGateway(
                new CollectionGatewayServiceImpl(new InMemoryCollectionAccessRepository())
            );
            $this->disbursementGateway = ApiGatewayFactory::loadMtnDisbursementGateway(
                new DisbursementGatewayServiceImpl(new InMemoryDisbursementAccessRepository())
            );

            return;
        }

        $this->collectionGateway = new CollectionGatewayService(new InMemoryCollectionAccessRepository());
        $this->disbursementGateway = new DisbursementGatewayService(new InMemoryDisbursementAccessRepository());
    }

    /**
     * @param int $amount
     * @param string $number
     * @return string
     * @throws CollectionException
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws Exception
     */
    public function executeCollect(int $amount, string $number): string
    {
        $reference = AbstractTools::uuid();
        $body = new CollectRequestBody(
            $amount,
            $number,
            $reference,
        );
        $collectSuccess = $this->collectionGateway->collect($body);

        if (!$collectSuccess) {
            throw new Exception("Collect not perform");
        }

        return $reference;
    }

    /**
     * @param string $reference
     * @return array
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function checkCollect(string $reference): array
    {
        return $this->collectionGateway->collectReference($reference);
    }

    /**
     * @return array
     * @throws MtnAccessKeyException
     * @throws RefreshAccessException
     * @throws TokenCreationException
     * @throws BalanceException
     */
    public function collectBalance(): array
    {
        return $this->collectionGateway->balance();
    }

    /**
     * @param int $amount
     * @param string $number
     * @return string
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws DisbursementException
     * @throws Exception
     */
    public function executeDisburse(int $amount, string $number): string
    {
        $reference = AbstractTools::uuid();
        $body = new DisburseRequestBody(
            $amount,
            $number,
            $reference,
        );
        $disburseSuccess = $this->disbursementGateway->disburse($body);

        if (!$disburseSuccess) {
            throw new Exception("Disburse not perform");
        }

        return $reference;
    }

    /**
     * @param string $reference
     * @return array
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     * @throws TransactionReferenceException
     */
    public function checkDisburse(string $reference): array
    {
        return $this->disbursementGateway->disburseReference($reference);
    }

    /**
     * @return array
     * @throws BalanceException
     * @throws MtnAccessKeyException
     * @throws TokenCreationException
     */
    public function disburseBalance(): array
    {
        return $this->disbursementGateway->balance();
    }

    /**
     * @return CollectionGatewayInterface
     */
    public function getCollectionGateway(): CollectionGatewayInterface
    {
        return $this->collectionGateway;
    }

    /**
     * @return DisbursementGatewayInterface
     */
    public function getDisbursementGateway(): DisbursementGatewayInterface
    {
        return $this->disbursementGateway;
    }

}