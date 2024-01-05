<?php

namespace Ekolotech\MoMoGateway\MtnGateway\Model;

class MtnAuthenticationProduct
{

    /**
     * @param string $subscriptionKeyOne
     * @param string $subscriptionKeyTwo
     * @param string|null $apiUser
     * @param string|null $apiKey
     */
    public function __construct(
        private readonly string $subscriptionKeyOne,
        private readonly string $subscriptionKeyTwo,
        private ?string         $apiUser = null,
        private ?string         $apiKey = null
    )
    {
    }

    /**
     * @return string|null
     */
    public function getApiUser(): ?string
    {
        return $this->apiUser;
    }

    /**
     * @param string|null $apiUser
     */
    public function setApiUser(?string $apiUser): void
    {
        $this->apiUser = $apiUser;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
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