<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Disbursement;

use Ekolotech\MoMoGateway\Api\Dto\DisburseRequestBody;

interface DisbursementGatewayInterface
{
    public function disburse(DisburseRequestBody $disburseRequestBody) : bool;
    public function disburseReference(string $reference) : array;
    public function balance() : array;
    public function isAccountIsActive(string $number) : bool;
    public function getAccountBasicInfo(string $number) : array;
}