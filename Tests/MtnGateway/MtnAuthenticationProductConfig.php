<?php

namespace Ekolotech\MoMoGateway\Tests\MtnGateway;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;

abstract class MtnAuthenticationProductConfig
{
    public static function collectionKeys(
        ?string $subscriptionKeyOne = null,
        ?string $subscriptionKeyTwo = null,
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            $subscriptionKeyOne ?? "2e3af0c3987147e691595030312a7e31",
            $subscriptionKeyTwo ?? "ea791d3137ca44e6b54a4b5ae7ede01f",
            $apiUser,
            $apiKey
        );
    }
    public static function disbursementKeys(
        ?string $subscriptionKeyOne = null,
        ?string $subscriptionKeyTwo = null,
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return new MtnAuthenticationProduct(
            $subscriptionKeyOne ?? "ac4f92d8be3e4801bd346d7a986cff52",
            $subscriptionKeyTwo ?? "a882e46cedd948b1abe31c513e4b822b",
            $apiUser,
            $apiKey
        );
    }
}