<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Collection;

use Ekolotech\MoMoGateway\Dto\CollectRequestBody;

interface CollectionGatewayInterface
{
    public function collect(CollectRequestBody $collectRequestBody): bool;

    public function collectReference(string $reference): array;

    public function balance(): array;

    public function isAccountIsActive(string $number): bool;

    public function getAccountBasicInfo(string $number): array;
}