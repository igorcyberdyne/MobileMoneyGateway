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
use Ekolotech\MoMoGateway\Api\Factory\ApiGatewayFactory;
use Ekolotech\MoMoGateway\Api\Helper\AbstractTools;
use Ekolotech\MoMoGateway\Api\Model\GatewayProductTypeEnum;
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
    public function __construct(bool $useFactory = true)
    {
        if ($useFactory) {
            $this->collectionGateway = ApiGatewayFactory::loadMtnGateway(
                GatewayProductTypeEnum::MtnCollectionGateway->name,
                new CollectionGatewayServiceImpl(new InMemoryCollectionAccessRepository())
            );
            $this->disbursementGateway = ApiGatewayFactory::loadMtnGateway(
                GatewayProductTypeEnum::MtnDisbursementGateway->name,
                new DisbursementGatewayServiceImpl(new InMemoryDisbursementAccessRepository())
            );

            return;
        }

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
    public function collectBalance(): array
    {
        return $this->collectionGateway->balance();
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
     * @throws Exception
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