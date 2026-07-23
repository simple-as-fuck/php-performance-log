<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Middleware;

use Illuminate\Http\Request;
use SimpleAsFuck\PerformanceLog\Listener\HttpServerListener;
use Symfony\Component\HttpFoundation\Response;

final readonly class LaravelMiddleware
{
    public function __construct(
        private HttpServerListener $httpServerListener,
    ) {
    }

    /**
     * @param \Closure(Request): Response $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $this->httpServerListener->onRequestStart();
        try {
            return $next($request);
        } finally {
            $this->httpServerListener->onRequestFinish($request->method(), $request->fullUrl());
        }
    }
}
