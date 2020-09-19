<?php

namespace DannyMeyer\Curl\Handler;

use GuzzleHttp\HandlerStack;

interface RetryHandlerInterface
{
    public function getAllowedRetries(): ?int;

    public function setAllowedRetries(?int $allowedRetries): void;

    public function getBaseRetryDelay(): int;

    public function setBaseRetryDelay(int $baseRetryDelay): void;

    public function getBaseRetryModuloDivisor(): int;

    public function setBaseRetryModuloDivisor(int $baseRetryModuloDivisor): void;

    public function createHandler(): HandlerStack;
}
