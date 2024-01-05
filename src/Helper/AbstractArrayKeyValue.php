<?php

namespace Ekolotech\MoMoGateway\Helper;



abstract class AbstractArrayKeyValue extends AbstractSingleton
{
    public static function getValue($id): ?string
    {
        return self::getItem($id)["value"] ?? null;
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

    protected function exist($id): bool
    {
        return in_array($id, array_column($this->list(), "id"));
    }

    protected abstract function list(): array;
}