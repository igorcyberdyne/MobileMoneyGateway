<?php

namespace Ekolotech\MoMoGateway\Helper;

use Ekolotech\MoMoGateway\Interface\ApiGatewayLoggerInterface;
use Throwable;

final class ProcessTracker
{
    /** @var mixed|null */
    protected mixed $callBackResponse = null;

    public function __construct(
        private readonly ?ApiGatewayLoggerInterface $apiGatewayLogger = null
    )
    {
    }

    /**
     * @return ApiGatewayLoggerInterface|null
     */
    public function getApiGatewayLogger(): ?ApiGatewayLoggerInterface
    {
        return $this->apiGatewayLogger;
    }

    /**
     * @param callable $callable
     * @param string $processId
     * @return mixed
     * @throws Throwable
     */
    public function start(callable $callable, string $processId): mixed
    {
        if (!is_callable($callable)) {
            return null;
        }

        $this->apiGatewayLogger?->getLogger()->info("[mobilemoney-gateway process] START <<<<<<<<<<< [$processId]");

        try {
            $this->callBackResponse = $callable($this->apiGatewayLogger);

        } catch (Throwable $t) {
            $this->apiGatewayLogger?->getLogger()->critical("Uncaught PHP Exception " . get_class($t) . ' : "' . $t->getMessage());
            $this->apiGatewayLogger?->getLogger()->critical($t->getTraceAsString());

            $throwable = $t;
        }

        $this->apiGatewayLogger?->getLogger()->info("[mobilemoney-gateway process] END >>>>>>>>>>> [$processId]");

        if (!empty($throwable)) {
            throw $throwable;
        }

        return $this->callBackResponse;
    }
}