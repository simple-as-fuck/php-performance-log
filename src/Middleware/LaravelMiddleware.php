<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Middleware;

use Illuminate\Http\Request;
use SimpleAsFuck\PerformanceLog\Listener\HttpListener;
use Symfony\Component\HttpFoundation\Response;

final readonly class LaravelMiddleware
{
    public function __construct(
        private HttpListener $httpListener,
    ) {
    }

    /**
     * @param \Closure(Request): Response $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $this->httpListener->onRequestStart();
        try {
            return $next($request);
        } finally {
            $this->httpListener->onRequestFinish($request->method(), $request->fullUrl());
        }
    }
}
