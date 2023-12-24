<?php

namespace Ekolotech\MobileMoney\Gateway\Api\Exception;

final class ExceptionMessage extends AbstractArrayKeyValue
{
    protected function list(): array
    {

        return [
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
                "value" => "Cannot retrieve account holder",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_REQUEST_ERROR,
                "value" => "Cannot retrieve account holder basic info",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_BASIC_CANNOT_BE_RETRIEVE,
                "value" => "Cannot retrieve account holder basic info",
            ],
            [
                "key" => AccountHolderException::ACCOUNT_HOLDER_BASIC_INFO_REQUEST_ERROR,
                "value" => "Error when checking account holder basic info",
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
        ];
    }
}