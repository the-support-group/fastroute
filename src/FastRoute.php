<?php

namespace Air\Routing\FastRoute;

use Air\Routing\Router\Router;
use Air\HTTP\Request\RequestInterface;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use Air\Routing\ResolvedRequest\ResolvedRequest;
use Air\Routing\ResolvedRequest\ResolvedRequestInterface;
use Exception;
use OutOfRangeException;

class FastRoute extends Router
{
    /**
     * @param RequestInterface $request A request.
     * @return ResolvedRequestInterface
     * @throws OutOfRangeException
     * @throws Exception
     */
    public function route(RequestInterface $request)
    {
        if (!is_null($this->cachePath)) {
            $dispatcher = \FastRoute\cachedDispatcher(
                function(RouteCollector $collector) {
                    foreach ($this->routes as $route) {
                        $collector->addRoute(
                            $route->getRequestType(),
                            $route->getUri(),
                            serialize($route)
                        );
                    }
                }, [
                    'cacheFile' => $this->cachePath
                ]
            );
        } else {
            // Add routes to the route collector.
            $dispatcher = \FastRoute\simpleDispatcher(
                function (RouteCollector $collector) {
                    foreach ($this->routes as $route) {
                        $collector->addRoute(
                            $route->getRequestType(),
                            $route->getUri(),
                            serialize($route)
                        );
                    }
                }
            );
        }

        // Dispatch the route.
        $route_info = $dispatcher->dispatch($request->getMethod(), $request->getUriPath());

        // Handle the route depending on the response type.
        switch ($route_info[0]) {
            // Route not found.
            case Dispatcher::NOT_FOUND:

                throw new OutOfRangeException("No route matched the given URI.");

            // Method not allowed for the specified route.
            case Dispatcher::METHOD_NOT_ALLOWED:

                throw new Exception("Method not allowed.");

            // Route found.
            case Dispatcher::FOUND:

                $route = $route_info[1];
                $params = $route_info[2];

                return new ResolvedRequest($request, unserialize($route), $params);
        }
    }
}
