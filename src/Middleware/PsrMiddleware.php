<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SimpleAsFuck\PerformanceLog\Listener\HttpListener;

final readonly class PsrMiddleware implements MiddlewareInterface
{
    public function __construct(
        private HttpListener $httpListener,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->httpListener->onRequestStart();
        try {
            return $handler->handle($request);
        } finally {
            $this->httpListener->onRequestFinish($request->getMethod(), (string) $request->getUri());
        }
    }
}
