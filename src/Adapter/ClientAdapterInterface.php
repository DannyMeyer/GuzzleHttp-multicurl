<?php

namespace DannyMeyer\Curl\Adapter;

use GuzzleHttp\ClientInterface;

/**
 * Class ClientAdapterInterface
 *
 * @package DannyMeyer\Curl\Adapter
 */
interface ClientAdapterInterface
{
    public function createClient(): ClientInterface;
}
