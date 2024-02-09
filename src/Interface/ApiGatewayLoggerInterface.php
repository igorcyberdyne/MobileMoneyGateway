<?php

namespace Ekolotech\MoMoGateway\Interface;

use Psr\Log\LoggerInterface;

interface ApiGatewayLoggerInterface
{
    public function getLogger(): LoggerInterface;
}