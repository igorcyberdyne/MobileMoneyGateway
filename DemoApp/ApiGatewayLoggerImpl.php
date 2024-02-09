<?php

namespace DemoApp;

use Ekolotech\MoMoGateway\Helper\LoggerImpl;
use Ekolotech\MoMoGateway\Interface\ApiGatewayLoggerInterface;
use Psr\Log\LoggerInterface;

class ApiGatewayLoggerImpl implements ApiGatewayLoggerInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new LoggerImpl();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}