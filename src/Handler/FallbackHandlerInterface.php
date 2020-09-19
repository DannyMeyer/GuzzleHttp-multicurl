<?php

namespace DannyMeyer\Curl\Handler;

use Psr\Http\Message\RequestInterface;

interface FallbackHandlerInterface
{
    public function execute(RequestInterface $request): ?string;
}
