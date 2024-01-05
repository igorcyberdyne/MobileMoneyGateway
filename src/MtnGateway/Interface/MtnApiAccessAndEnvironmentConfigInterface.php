<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Interface;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAccessToken;
use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;

interface MtnApiAccessAndEnvironmentConfigInterface extends MtnApiEnvironmentConfigInterface, MtnApiAccessConfigListenerInterface
{
    public function getMtnAuthenticationProduct() : MtnAuthenticationProduct;
    public function getMtnAccessToken(): ?MtnAccessToken;

}