<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->routes[] = ['GET', $path, $controller, $action];
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->routes[] = ['POST', $path, $controller, $action];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        foreach ($this->routes as [$routeMethod, $routePath, $controllerName, $action]) {
            if ($routeMethod !== $method) continue;

            $pattern = $this->pathToRegex($routePath);
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $controllerClass = "App\\Controllers\\{$controllerName}";
                $controller = new $controllerClass();
                $controller->$action($request, ...$matches);
                return;
            }
        }

        http_response_code(404);
        echo "404 - Page not found";
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
