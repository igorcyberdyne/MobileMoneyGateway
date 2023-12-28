<?php

namespace DemoApp;

use Exception;
use RuntimeException;

abstract class Config
{
    public static function projectDir(): string
    {
        return __DIR__;
    }
    public static function dataDir(): string
    {
        $dir = self::projectDir() . "/Data";

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
}