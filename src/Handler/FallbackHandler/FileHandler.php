<?php

namespace DannyMeyer\Curl\Handler\FallbackHandler;

use DannyMeyer\Curl\Handler\FallbackHandlerInterface;
use Psr\Http\Message\RequestInterface;

use function file_get_contents;

class FileHandler implements FallbackHandlerInterface
{
    // @todo return Response object instead; With error message in case of erro
    // @todo respect several request options like authentication, get / post
    public function execute(RequestInterface $request): ?string
    {
        return @file_get_contents((string)$request->getUri()) ?: null;
    }
}
