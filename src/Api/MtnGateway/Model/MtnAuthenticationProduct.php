<?php

namespace Ekolotech\MobileMoney\Gateway\Api\MtnGateway\Model;

class MtnAuthenticationProduct
{

    /**
     * @param string $apiUser
     * @param string $subscriptionKeyOne
     * @param string $subscriptionKeyTwo
     * @param string|null $apiKey
     */
    public function __construct(
        private readonly string $apiUser,
        private readonly string $subscriptionKeyOne,
        private readonly string $subscriptionKeyTwo,
        private ?string $apiKey = null
    )
    {
    }

    /**
     * @return string
     */
    public function getApiUser(): string
    {
        return $this->apiUser;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return MtnAuthenticationProduct
     */
    public function setApiKey(string $apiKey): MtnAuthenticationProduct
    {
        $this->apiKey = $apiKey;

        return $this;
    }


    /**
     * @return string
     */
    public function getSubscriptionKeyOne(): string
    {
        return $this->subscriptionKeyOne;
    }

    /**
     * @return string
     */
    public function getSubscriptionKeyTwo(): string
    {
        return $this->subscriptionKeyTwo;
    }
}