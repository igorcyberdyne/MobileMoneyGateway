<?php

namespace Ekolotech\MobileMoney\Gateway\Api\Exception;

class ProductTokenSessionException extends ApiGatewayException
{
    const PRODUCT_TOKEN_SESSION_NOT_FOUND = 3000;
    const PRODUCT_TOKEN_SESSION_EXPIRED = 3001;
    const PRODUCT_TOKEN_SESSION_CANNOT_BE_CREATE = 3002;
}