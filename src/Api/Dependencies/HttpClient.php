<?php

namespace Ekolotech\MoMoGateway\Api\Dependencies;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

final class HttpClient
{
    /**
     * @param array $defaultOptions Default request's options
     * @param int $maxHostConnections The maximum number of connections to a single host
     * @param int $maxPendingPushes The maximum number of pushed responses to accept in the queue
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     * @throws Exception
     */
    public static function create(array $defaultOptions = [], int $maxHostConnections = 6, int $maxPendingPushes = 50): HttpClientInterface
    {
        return SymfonyHttpClient::create($defaultOptions, $maxHostConnections, $maxPendingPushes);
    }
}