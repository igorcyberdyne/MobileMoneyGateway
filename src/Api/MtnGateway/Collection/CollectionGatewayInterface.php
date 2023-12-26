<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Collection;

use Ekolotech\MobileMoney\Gateway\Api\Dto\CollectRequestBody;

interface CollectionGatewayInterface
{
    public function collect(CollectRequestBody $collectRequestBody) : bool;
    public function collectReference(string $reference) : array;
    public function balance() : array;

}