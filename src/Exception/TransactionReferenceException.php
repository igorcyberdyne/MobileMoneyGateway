<?php

namespace Ekolotech\MoMoGateway\Exception;

class TransactionReferenceException extends ApiGatewayException
{
    const TRANSACTION_REFERENCE_CANNOT_BE_RETRIEVE = 5000;
    const TRANSACTION_REFERENCE_REQUEST_ERROR = 5001;
}