<?php

namespace Ekolotech\MobileMoney\Gateway\Api\Exception;

class AccountHolderException extends ApiGatewayException
{
    const ACCOUNT_HOLDER_CANNOT_BE_RETRIEVE = 6000;
    const ACCOUNT_HOLDER_REQUEST_ERROR = 6001;
    const ACCOUNT_HOLDER_BASIC_CANNOT_BE_RETRIEVE = 6002;
    const ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR = 6003;
    const ACCOUNT_HOLDER_BAD_NUMBER = 6004;
}