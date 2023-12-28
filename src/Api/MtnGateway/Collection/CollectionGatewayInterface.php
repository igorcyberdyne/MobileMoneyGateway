<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Api\Dto\CollectRequestBody;

interface CollectionGatewayInterface
{
    public function collect(CollectRequestBody $collectRequestBody) : bool;
    public function collectReference(string $reference) : array;
    public function balance() : array;
    public function isAccountIsActive(string $number) : bool;
    public function getAccountBasicInfo(string $number) : array;
}