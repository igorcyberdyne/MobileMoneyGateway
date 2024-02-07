<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

interface MtnApiCollectionErrorListenerInterface
{
    public function onCollectError(
        string $reference,
        array  $data
    ): void;

    public function onCollectReferenceError(
        string $reference,
        array  $data
    ): void;
}