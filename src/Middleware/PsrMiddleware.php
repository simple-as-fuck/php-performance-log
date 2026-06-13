<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleAsFuck\PerformanceLog\Listener\HttpServerListener;

final readonly class PsrMiddleware implements MiddlewareInterface
{
    public function __construct(
        private HttpServerListener $httpServerListener,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->httpServerListener->onRequestStart();
        $response = $handler->handle($request);
        $this->httpServerListener->onRequestFinish($request->getMethod(), (string) $request->getUri());
        return $response;
    }
}
