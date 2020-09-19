<?php

namespace DannyMeyer\Curl\Adapter;

use DannyMeyer\Curl\Handler\RetryHandler;
use DannyMeyer\Curl\Handler\RetryHandlerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Class ClientAdapter
 *
 * @package DannyMeyer\Curl\Adapter
 */
class ClientAdapter implements ClientAdapterInterface
{
    /** @var RetryHandlerInterface */
    private $retryHandler;

    public function __construct(
        RetryHandlerInterface $retryHandler = null
    ) {
        $this->retryHandler = $retryHandler ?? new RetryHandler();
    }

    public function createClient(): ClientInterface
    {
        return new Client(
            [
                'handler' => $this->retryHandler->createHandler(),
                'verify' => false,
            ]
        );
    }
}
