<?php

namespace Ekolotech\MoMoGateway\Exception;

class CollectionException extends ApiGatewayException
{
    const REQUEST_TO_PAY_NOT_PERFORM = 8000;
    const REQUEST_TO_PAY_EMPTY_PAYMENT_PARAM = 8001;
    const REQUEST_TO_PAY_AMOUNT_CANNOT_BE_MINUS_ZERO = 8002;
    const REQUEST_TO_PAY_BAD_NUMBER = 8003;
}