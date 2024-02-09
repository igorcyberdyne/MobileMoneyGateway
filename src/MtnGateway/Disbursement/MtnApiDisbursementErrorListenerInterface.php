<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Disbursement;

interface MtnApiDisbursementErrorListenerInterface
{
    public function onDisburseError(
        string $reference,
        array  $data
    ): void;

    public function onDisburseReferenceError(
        string $reference,
        array  $data
    ): void;
}