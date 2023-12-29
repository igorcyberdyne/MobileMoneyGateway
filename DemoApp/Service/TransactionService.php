<?php

namespace DemoApp\Service;

use DemoApp\RepositoryImpl\InMemoryCollectionAccessRepository;
use DemoApp\RepositoryImpl\InMemoryDisbursementAccessRepository;
use Ekolotech\MoMoGateway\Api\Dto\CollectRequestBody;
use Ekolotech\MoMoGateway\Api\Dto\DisburseRequestBody;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\MtnGateway\Collection\CollectionGatewayInterface;
use Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement\DisbursementGatewayInterface;
use Exception;

final class TransactionService
{
    private CollectionGatewayInterface $collectionGateway;
    private DisbursementGatewayInterface $disbursementGateway;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->collectionGateway = new CollectionGatewayService(new InMemoryCollectionAccessRepository());
        $this->disbursementGateway = new DisbursementGatewayService(new InMemoryDisbursementAccessRepository());
    }

    /**
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
     * @throws Exception
     */
    public function checkCollect(string $reference): array
    {
        return $this->collectionGateway->collectReference($reference);
    }

    /**
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
     * @throws Exception
     */
    public function checkDisburse(string $reference): array
    {
        return $this->disbursementGateway->disburseReference($reference);
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