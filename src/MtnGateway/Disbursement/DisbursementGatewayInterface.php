<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Dto\DisburseRequestBody;

interface DisbursementGatewayInterface
{
    public function disburse(DisburseRequestBody $disburseRequestBody): bool;

    public function disburseReference(string $reference): array;

    public function balance(): array;

    public function isAccountIsActive(string $number): bool;

    public function getAccountBasicInfo(string $number): array;
}