<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;

interface MtnApiAccessConfigErrorListenerInterface
{
    public function onApiUserCreationError(
        MtnAuthenticationProduct $mtnAuthenticationProduct,
        array $data
    ): void;

    public function onApiKeyCreationError(
        MtnAuthenticationProduct $mtnAuthenticationProduct,
        array $data
    ): void;

    public function onTokenCreationError(
        MtnAuthenticationProduct $mtnAuthenticationProduct,
        array $data
    ): void;

}