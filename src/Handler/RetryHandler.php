<?php

namespace DannyMeyer\Curl\Handler;

use Closure;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RetryHandler
 *
 * @package DannyMeyer\Curl
 */
class RetryHandler implements RetryHandlerInterface
{
    protected const HTTP_RESPONSE_SERVER_ERROR_MIN = 500;
    protected const HTTP_RESPONSE_SERVER_ERROR_MAX = 599;

    /**
     * Number of allowed retries until we give up
     *
     * default: 5
     * unlimited: null
     *
     * @var int|null
     */
    protected $allowedRetries = 1;

    /**
     * Base delay between retries in milliseconds
     *
     * @var int
     */
    protected $baseRetryDelay = 2000;

    /**
     * Divisor for retry delay calculation
     *
     * Formula: $baseRetryDelay * ($numberOfRetries % $baseRetryModuloDivisor + 1)
     *
     * @var int
     */
    protected $baseRetryModuloDivisor = 2;

    public function getBaseRetryDelay(): int
    {
        return $this->baseRetryDelay;
    }

    public function setBaseRetryDelay(int $baseRetryDelay): void
    {
        $this->baseRetryDelay = $baseRetryDelay;
    }

    public function getBaseRetryModuloDivisor(): int
    {
        return $this->baseRetryModuloDivisor;
    }

    public function setBaseRetryModuloDivisor(int $baseRetryModuloDivisor): void
    {
        $this->baseRetryModuloDivisor = $baseRetryModuloDivisor;
    }

    public function createHandler(): HandlerStack
    {
        $handlerStack = HandlerStack::create(new CurlMultiHandler());

        $handlerStack->push(
            Middleware::retry(
                $this->retryDecider(),
                $this->retryDelay()
            )
        );

        return $handlerStack;
    }

    protected function retryDecider(): Closure
    {
        $allowedRetries = $this->getAllowedRetries();

        return static function (
            $retries,
            /** @noinspection PhpUnusedParameterInspection */
            RequestInterface $request,
            ResponseInterface $response = null,
            Exception $exception = null
        ) use ($allowedRetries) {
            // Limit the number of retries
            if (
                $allowedRetries !== null
                && $retries > $allowedRetries
            ) {
                return false;
            }

            // Retry connection exceptions
            if (
                $exception instanceof ConnectException
                || $exception instanceof Promise\AggregateException
            ) {
                return true;
            }

            // Retry when something is wrong with the response
            if ($response instanceof ResponseInterface === false) {
                return true;
            }

            $responseStatusCode = $response->getStatusCode();

            // Retry on server errors
            /** @noinspection IfReturnReturnSimplificationInspection */
            if ($responseStatusCode >= self::HTTP_RESPONSE_SERVER_ERROR_MIN
                && $responseStatusCode <= self::HTTP_RESPONSE_SERVER_ERROR_MAX
            ) {
                return true;
            }

            return false;
        };
    }

    public function getAllowedRetries(): ?int
    {
        return $this->allowedRetries;
    }

    public function setAllowedRetries(?int $allowedRetries): void
    {
        $this->allowedRetries = $allowedRetries;
    }

    protected function retryDelay(): Closure
    {
        $baseRetryDelay = $this->baseRetryDelay;
        $baseRetryModuloDivisor = $this->baseRetryModuloDivisor;

        return static function ($numberOfRetries) use ($baseRetryDelay, $baseRetryModuloDivisor) {
            return $baseRetryDelay * ($numberOfRetries % $baseRetryModuloDivisor + 1);
        };
    }
}
