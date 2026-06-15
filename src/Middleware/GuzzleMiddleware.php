<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimpleAsFuck\PerformanceLog\Listener\HttpClientListener;

final readonly class GuzzleMiddleware
{
    public function __construct(
        private HttpClientListener $httpClientListener,
    ) {
    }

    /**
     * @param callable(RequestInterface $request, array<mixed> $options): PromiseInterface $handler
     * @return callable(RequestInterface $request, array<mixed> $options): PromiseInterface
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $this->httpClientListener->onRequestStart($request);
            return $handler($request, $options)->then(
                onFulfilled: function (ResponseInterface $response) use ($request): ResponseInterface {
                    $this->httpClientListener->onRequestFinish($request);
                    return $response;
                },
                onRejected: function (\Throwable $exception) use ($request): never {
                    $this->httpClientListener->onRequestFinish($request);
                    throw $exception;
                },
            );
        };
    }
}
