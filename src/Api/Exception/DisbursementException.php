<?php

namespace Ekolotech\MoMoGateway\Api\Exception;

class DisbursementException extends ApiGatewayException
{
    const DISBURSE_NOT_PERFORM = 9000;
    const DISBURSE_EMPTY_PAYMENT_PARAM = 9001;
    const DISBURSE_AMOUNT_CANNOT_BE_MINUS_ZERO = 9002;
    const DISBURSE_BAD_NUMBER = 9003;
}