<?php

namespace Ekolotech\MobileMoney\Gateway\Api\Exception;

class TransactionReferenceException extends ApiGatewayException
{
    const TRANSACTION_REFERENCE_CANNOT_BE_RETRIEVE = 5000;
    const TRANSACTION_REFERENCE_REQUEST_ERROR = 5001;
}