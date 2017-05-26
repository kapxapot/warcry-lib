<?php

namespace Warcry\Slim\Middleware;

class JsonResponseMiddleware extends Middleware {
	public function __invoke($request, $response, $next) {
		return $next($request, $response->withHeader('Content-Type', 'application/json'));
	}
}