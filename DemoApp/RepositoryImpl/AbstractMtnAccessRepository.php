<?php

namespace DemoApp\RepositoryImpl;

use DateTime;
use Ekolotech\MoMoGateway\Api\MtnGateway\Model\MtnAccessToken;
use Exception;

abstract class AbstractMtnAccessRepository extends AbstractFileStoreRepository
{
    /**
     * @throws Exception
     */
    public function getApiKey(): ?string
    {
        return $this->getStore()["apiKey"] ?? null;
    }

    /**
     * @throws Exception
     */
    public function saveApiKey(string $apiKey): static
    {
        $this->setStore(["apiKey" => $apiKey]);

        return $this;
    }


    /**
     * @throws Exception
     */
    public function getMtnAccessToken(): ?MtnAccessToken
    {
        if (empty($this->getStore()["mtnAccessToken"])) {
            return null;
        }

        try {
            $isExpired = strtotime(date("Y-m-d H:i:s")) >= strtotime((new DateTime($this->getStore()["mtnAccessToken"]["expiredAt"]))->modify("-10 minutes")->format("Y-m-d H:i:s"));
        }
        catch (Exception $e) {
            $isExpired = true;
        }

        return new MtnAccessToken(
            $this->getStore()["mtnAccessToken"]["accessToken"],
            $this->getStore()["mtnAccessToken"]["tokenType"],
            $this->getStore()["mtnAccessToken"]["expiresIn"],
            $isExpired
        );
    }

    /**
     * @throws Exception
     */
    public function saveMtnAccessToken(MtnAccessToken $mtnAccessToken): static
    {
        $expiredMinutes = $mtnAccessToken->getExpiresIn() / 60;

        $expirationDate = new DateTime();
        $expirationDate->modify("+" . (int) ($mtnAccessToken->getExpiresIn() > 60 ? $expiredMinutes : 10) . " minutes");

        $this->setStore([
            "mtnAccessToken" => [
                "accessToken" => $mtnAccessToken->getAccessToken(),
                "tokenType" => $mtnAccessToken->getTokenType(),
                "expiresIn" => $mtnAccessToken->getExpiresIn(),
                "expiredAt" => $expirationDate->format("Y-m-d H:i:s")
            ]
        ]);

        return $this;
    }

}