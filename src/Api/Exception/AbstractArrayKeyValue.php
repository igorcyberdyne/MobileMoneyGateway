<?php

namespace Ekolotech\MoMoGateway\Api\Exception;

use Ekolotech\MoMoGateway\Api\Helper\AbstractSingleton;

abstract class AbstractArrayKeyValue extends AbstractSingleton
{
    protected abstract function list() : array;

    protected function exist($id): bool {
        return in_array($id, array_column($this->list(), "id"));
    }

    public static function getItem($key): ?array
    {
        if (empty($key)) {
            return null;
        }

        foreach (self::getInstance()->list() as $item) {
            if (strtolower($item["key"] ?? "-") != strtolower($key)) {
                continue;
            }

            return $item;
        }

        return null;
    }

    public static function getValue($id): ?string
    {
        return self::getItem($id)["value"] ?? null;
    }
}