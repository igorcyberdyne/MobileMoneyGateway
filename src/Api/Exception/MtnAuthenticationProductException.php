<?php

namespace Ekolotech\MoMoGateway\Api\Exception;

class MtnAuthenticationProductException extends ApiGatewayException
{
    const PRODUCT_MUST_BE_CONFIGURED = 10000;
    const API_KEY_CANNOT_BE_EMPTY = 10001;
    const API_USER_CANNOT_BE_EMPTY = 10002;
}