<?php

namespace DannyMeyer\Curl;

use DannyMeyer\Curl\Adapter\ClientAdapter;
use DannyMeyer\Curl\Adapter\ClientAdapterInterface;
use DannyMeyer\Curl\Handler\FallbackHandler\FileHandler;
use DannyMeyer\Curl\Handler\FallbackHandlerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use function array_replace;
use function count;
use function GuzzleHttp\Promise\settle;
use function is_string;

/**
 * Class Multicurl
 *
 * @package DannyMeyer\Curl
 */
class Multicurl
{
    /** @var bool */
    protected $useFallback = false;

    /** @var RequestInterface[] */
    protected $dataset = [];

    protected $errors = [];

    /** @var ClientAdapterInterface */
    private $clientAdapter;

    /** @var FallbackHandlerInterface $fallbackHandler */
    private $fallbackHandler;

    public function __construct(
        ClientAdapterInterface $clientAdapter = null,
        FallbackHandlerInterface $fallbackHandler = null
    ) {
        $this->clientAdapter = $clientAdapter ?? new ClientAdapter();
        $this->fallbackHandler = $fallbackHandler ?? new FileHandler();
    }

    public function addGetRequestByUri($uri, ?string $id = null): void
    {
        $this->addRequestAndValidate('GET', $uri, $id);
    }

    public function addPostRequestByUri($uri, ?string $id = null): void
    {
        $this->addRequestAndValidate('POST', $uri, $id);
    }

    private function addRequestAndValidate(string $method, $uri, ?string $id = null): void
    {
        if (
            $uri instanceof UriInterface === false
            && is_string($uri) === false
        ) {
            new InvalidArgumentException('Request is not a string nor an instance of \Psr\Http\Message\UriInterface');
        }

        $this->addRequest(new Request($method, $uri), $id);
    }

    public function addRequest(RequestInterface $request, ?string $id = null): void
    {
        if ($id === null) {
            $this->dataset[] = $request;
            return;
        }

        $this->dataset[$id] = $request;
    }

    public function activateFallback(): void
    {
        $this->useFallback = true;
    }

    public function removeRequest(string $id): void
    {
        unset($this->dataset[$id]);
    }

    public function clear(): void
    {
        $this->dataset = [];
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Execute multicurl request
     *
     * @return array
     * @see  https://guzzle.readthedocs.io/en/latest/quickstart.html#concurrent-requests
     *
     * @uses Client
     */
    public function execute(): array
    {
        $client = $this->clientAdapter->createClient();

        $promises = [];
        $result = [];
        $return = [];
        $errors = [];
        $dataset = $this->dataset;

        foreach ($dataset as $id => $request) {
            $return[$id] = null;
            $promises[$id] = $client->sendAsync($request);
        }

        $responses = settle($promises)->wait();

        foreach ($responses as $id => $response) {
            if ($response['state'] === PromiseInterface::REJECTED) {
                $message = 'unknown';

                if ($response['reason'] instanceof Exception) {
                    $message = $response['reason']->getMessage();
                }

                $errors[$id] = $message;

                continue;
            }

            /** @var Response $data */
            $data = $response['value'];
            $body = $data->getBody();

            $result[$id] = $body->getContents();
            $body->close();
        }

        $this->errors = $errors;

        if (
            $this->useFallback === true
            && count($result) !== count($this->dataset)
        ) {
            foreach ($dataset as $id => $request) {
                if (isset($result[$id])) {
                    continue;
                }

                $result[$id] = $this->fallbackHandler->execute($request);
            }
        }

        return array_replace($return, $result);
    }
}
