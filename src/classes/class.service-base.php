<?php declare(strict_types=1);

namespace YeAPF\Services;

abstract class ServiceBase
{
    protected array $routes = [];

    public function __construct()
    {
        $this->routes = [];
    }

    /**
     * Registers a route with a security policy.
     */
    public function setRouteHandler(
        string $path, 
        callable $handler, 
        array $methods = ['GET']): RouteDefinition
    {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException("Handler for $path must be a callable.");
        }
        $route               = new RouteDefinition($path, $handler, $methods);
        $this->routes[$path] = $route;
        return $route;
    }

    /**
     * Validates security based on the route definition.
     */
    protected function checkSecurity(RouteDefinition $route, array $requestData): bool
    {
        $authMethod = $route->getAuthMethod();
        switch ($authMethod) {
            case 'jwt':
                return $this->validateJWT($requestData['jwt'] ?? '');
            case 'basicAuth':
                return $this->validateBasicAuth();
            case 'apiKey':
                return $this->validateApiKey($requestData['apiKey'] ?? '');
            default:
                return false;
        }
    }

    /**
     * JWT validation (override if needed).
     */
    protected function validateJWT(string $jwt): bool
    {
        return !empty($jwt);  // Replace with real validation logic
    }

    /**
     * Basic Auth validation.
     */
    protected function validateBasicAuth(): bool
    {
        return isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    /**
     * API Key validation.
     */
    protected function validateApiKey(string $apiKey): bool
    {
        return !empty($apiKey) && $apiKey === 'your-secret-key';  // Replace with real validation
    }
}
