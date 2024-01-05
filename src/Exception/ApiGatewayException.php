<?php

namespace Ekolotech\MoMoGateway\Exception;

use Ekolotech\MoMoGateway\Helper\AbstractTools;
use Exception;
use InvalidArgumentException;
use Throwable;

class ApiGatewayException extends Exception implements Throwable
{
    protected static array $_instance = [];
    protected string $errorKey = "ApiGateway error #";

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct(!empty($message) ? "# $message." : $this->errorKey . $code, $code, $previous);
    }

    public static function load(int $code, $params = [], Throwable $previous = null): static
    {
        $message = ExceptionMessage::getValue($code) ?? null;

        if (empty($message)) {
            $message = "Message not found for code '$code' given in '" . ExceptionMessage::class . "'\n";
            $message .= "Complete that message related to exception '" . get_called_class() . "'";

            throw new InvalidArgumentException($message);
        }

        foreach ($params as $key => $value) {
            $message = str_replace("[$key]", $value, $message);
        }

        return static::getInstance($message, $code, $previous);
    }

    private static function getInstance(string $message, int $code, Throwable $previous = null): static
    {
        $className = get_called_class();
        $className .= AbstractTools::slugify("$className-$message-$code");

        if (!empty(static::$_instance[$className])) {
            return static::$_instance[$className];
        }

        try {
            static::$_instance[$className] = new static($message, $code, $previous);
        } catch (Exception) {
            static::$_instance[$className] = new static();
        }

        return static::$_instance[$className];
    }

    public function getCodeOrigin(): ?int
    {
        return $this->getPrevious()?->getCode();
    }

    public function getMessageOrigin(): ?string
    {
        return $this->getPrevious()?->getMessage();
    }
}