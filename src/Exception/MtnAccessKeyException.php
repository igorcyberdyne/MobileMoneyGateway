<?php

namespace Ekolotech\MoMoGateway\Exception;

class MtnAccessKeyException extends ApiGatewayException
{
    const CANNOT_PERFORM_REQUEST_TO_CREATE_API_KEY = 12000;
    const CANNOT_PERFORM_REQUEST_TO_CREATE_API_USER = 12001;
    const CANNOT_PERFORM_REQUEST_TO_RETRIEVE_API_USER = 12002;
    const CANNOT_CREATE_API_KEY_IN_PRODUCTION = 12003;
}