<?php

namespace Ekolotech\MoMoGateway\Api\Helper;


class AbstractSingleton
{
    protected static array $_instance = [];
    final public static function getInstance(): static
    {
        $className = get_called_class();

        if (empty(static::$_instance[$className])) {
            static::$_instance[$className] = new static();
        }

        return static::$_instance[$className];
    }


}