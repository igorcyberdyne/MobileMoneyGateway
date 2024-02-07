<?php

namespace DemoApp;

use Ekolotech\MoMoGateway\MtnGateway\Model\MtnAuthenticationProduct;
use Ekolotech\MoMoGateway\Tests\MtnGateway\MtnAuthenticationProductConfig;
use Exception;
use RuntimeException;

abstract class Config
{
    private static function externalProjectDir(): string
    {
        return dirname(__DIR__) . '/../../..';
    }

    private static function isLibraryInVendorDir(): string
    {
        return file_exists(self::externalProjectDir() . '/vendor/autoload.php');
    }

    public static function relativeProjectDir(): string
    {
        return !self::isLibraryInVendorDir() ? __DIR__ : self::externalProjectDir();
    }

    public static function dataDir(): string
    {
        $dir = self::relativeProjectDir() . (!self::isLibraryInVendorDir() ? "/RepositoryData" : "/MomoGatewayRepoData");

        self::createDir($dir);

        return $dir;
    }

    /**
     * @param string $dir
     * @return void
     */
    private static function createDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        try {
            mkdir($dir);
        } catch (Exception $exception) {
            throw new RuntimeException("Error to load dir : " . $exception->getMessage(), $exception->getCode());
        }
    }

    public static function collectionKeys(
        ?string $subscriptionKeyOne = null,
        ?string $subscriptionKeyTwo = null,
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return MtnAuthenticationProductConfig::collectionKeys(
            $subscriptionKeyOne,
            $subscriptionKeyTwo,
            $apiUser,
            $apiKey,
        );
    }

    public static function disbursementKeys(
        ?string $subscriptionKeyOne = null,
        ?string $subscriptionKeyTwo = null,
        ?string $apiUser = null,
        ?string $apiKey = null
    ): MtnAuthenticationProduct
    {
        return MtnAuthenticationProductConfig::collectionKeys(
            $subscriptionKeyOne,
            $subscriptionKeyTwo,
            $apiUser,
            $apiKey,
        );
    }
}