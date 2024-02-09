<?php

namespace Ekolotech\MoMoGateway\Helper;

use Psr\Log\LoggerInterface;
use Stringable;

class LoggerImpl implements LoggerInterface {
    private array $logger;

    public function __construct()
    {
        $this->logger = [];
    }

    /**
     * @return array
     */
    public function getLoggerMessages(): array
    {
        return $this->logger;
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->logger["emergency"][] = $message;
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->logger["alert"][] = $message;
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->logger["critical"][] = $message;
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->logger["error"][] = $message;
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->logger["warning"][] = $message;
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->logger["notice"][] = $message;
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->logger["info"][] = $message;
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->logger["debug"][] = $message;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger["log"][] = $message;
    }

}