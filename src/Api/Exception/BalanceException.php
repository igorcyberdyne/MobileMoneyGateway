<?php

namespace Ekolotech\MoMoGateway\Api\Exception;


class BalanceException extends ApiGatewayException
{
    const BALANCE_CANNOT_BE_RETRIEVE = 7000;
    const BALANCE_REQUEST_ERROR = 7001;
}