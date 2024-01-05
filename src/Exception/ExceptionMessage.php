<?php

namespace Ekolotech\MoMoGateway\Exception;

use Ekolotech\MoMoGateway\Helper\AbstractArrayKeyValue;

final class ExceptionMessage extends AbstractArrayKeyValue
{
    protected function list(): array
    {
        return [
            [
                "key" => ProductTokenSessionException::PRODUCT_TOKEN_SESSION_NOT_FOUND,
                "value" => "Product token session not found",
            ],
            [
                "key" => ProductTokenSessionException::PRODUCT_TOKEN_SESSION_CANNOT_BE_CREATE,
                "value" => "Product token session cannot be created",
            ],
            [
                "key" => TokenCreationException::TOKEN_CREATION_ERROR,
                "value" => "Token creation error",
            ],
            [
                "key" => TransactionReferenceException::TRANSACTION_REFERENCE_CANNOT_BE_RETRIEVE,
                "value" => "Cannot retrieve transaction reference",
            ],
            [
                "key" => TransactionReferenceException::TRANSACTION_REFERENCE_REQUEST_ERROR,
                "value" => "Error when checking transaction reference",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_CANNOT_BE_RETRIEVE,
                "value" => "Cannot check if account holder is active",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_REQUEST_ERROR,
                "value" => "Error when checking is account holder is active",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_BASIC_CANNOT_BE_RETRIEVE,
                "value" => "Cannot retrieve account holder basic info",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR,
                "value" => "Error when getting account holder basic info",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_BAD_NUMBER,
                "value" => "Bad number given",
            ],
            [
                "key" => BalanceException::BALANCE_CANNOT_BE_RETRIEVE,
                "value" => "Cannot retrieve the balance",
            ],
            [
                "key" => BalanceException::BALANCE_REQUEST_ERROR,
                "value" => "Error when checking  the balance",
            ],
            [
                "key" => CollectionException::REQUEST_TO_PAY_NOT_PERFORM,
                "value" => "Error when launching request to pay",
            ],
            [
                "key" => CollectionException::REQUEST_TO_PAY_EMPTY_PAYMENT_PARAM,
                "value" => "The params [param] cannot be empty",
            ],
            [
                "key" => CollectionException::REQUEST_TO_PAY_AMOUNT_CANNOT_BE_MINUS_ZERO,
                "value" => "The amount must be strictly greater than 0",
            ],
            [
                "key" => CollectionException::REQUEST_TO_PAY_BAD_NUMBER,
                "value" => "Bad number given",
            ],
            [
                "key" => CollectionException::REQUEST_TO_PAY_BAD_REFERENCE_UUID,
                "value" => "Bad reference given. It's must be a Version 4 of UUID",
            ],
            [
                "key" => DisbursementException::DISBURSE_NOT_PERFORM,
                "value" => "Error when launching disbursement",
            ],
            [
                "key" => DisbursementException::DISBURSE_EMPTY_PAYMENT_PARAM,
                "value" => "The params [param] cannot be empty",
            ],
            [
                "key" => DisbursementException::DISBURSE_AMOUNT_CANNOT_BE_MINUS_ZERO,
                "value" => "The amount must be strictly greater than 0",
            ],
            [
                "key" => DisbursementException::DISBURSE_BAD_NUMBER,
                "value" => "Bad number given",
            ],
            [
                "key" => DisbursementException::DISBURSE_BAD_REFERENCE_UUID,
                "value" => "Bad reference given. It's must be a Version 4 of UUID",
            ],
            [
                "key" => MtnAuthenticationProductException::PRODUCT_MUST_BE_CONFIGURED,
                "value" => "Mtn product must be configured",
            ],
            [
                "key" => MtnAuthenticationProductException::API_KEY_CANNOT_BE_EMPTY,
                "value" => "Mtn apiKey cannot be empty",
            ],
            [
                "key" => MtnAuthenticationProductException::API_USER_CANNOT_BE_EMPTY,
                "value" => "Mtn apiUser cannot be empty",
            ],
            [
                "key" => EnvironmentException::MTN_ENV_NOT_CONFIGURED,
                "value" => "Mtn environment not configured",
            ],
            [
                "key" => MtnAccessKeyException::CANNOT_CREATE_API_KEY_IN_PRODUCTION,
                "value" => "Api key not be configured in production",
            ],
            [
                "key" => MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_RETRIEVE_API_USER,
                "value" => "Error when retrieving api user",
            ],
            [
                "key" => MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_USER,
                "value" => "Error when creating api user",
            ],
            [
                "key" => MtnAccessKeyException::CANNOT_PERFORM_REQUEST_TO_CREATE_API_KEY,
                "value" => "Error when creating api key",
            ],
            [
                "key" => FactoryException::CANNOT_CREATE_OBJECT_WITH_TYPE,
                "value" => "Gateway with type [type] does not exist",
            ],
            [
                "key" => RefreshAccessException::REFRESH_ACCESS_ERROR,
                "value" => "Error when refreshing access keys. See code: [[code]]; message: [[message]]",
            ],
        ];
    }
}