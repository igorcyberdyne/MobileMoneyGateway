<?php

namespace Ekolotech\MoMoGateway\Api\MtnGateway\Interface;

use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAuthenticationProduct;

interface MtnApiAccessAndEnvironmentConfigInterface extends MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
{
    public function getMtnAuthenticationProduct() : MtnAuthenticationProduct;
    public function getMtnAccessToken(): ?MtnAccessToken;

}