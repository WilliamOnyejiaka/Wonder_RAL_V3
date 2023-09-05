<?php
declare(strict_types=1);

namespace Lib;

require __DIR__ . "/../vendor/autoload.php";

ini_set("display_errors", 1);

class BaseRouter
{

    protected array $handlers;
    protected array $middlewares;

    protected const METHOD_GET = "GET";
    protected const METHOD_POST = "POST";
    protected const METHOD_PUT = "PUT";
    protected const METHOD_PATCH = "PATCH";
    protected const METHOD_DELETE = "DELETE";
    protected string $url_prefix;


    public function __construct()
    {
    }

    public function get(string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler(self::METHOD_GET, $path, $callback, $type, $name);
        return $this;
    }

    public function post(string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler(self::METHOD_POST, $path, $callback, $type, $name);
        return $this;
    }

    public function put(string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler(self::METHOD_PUT, $path, $callback, $type, $name);
        return $this;
    }

    public function patch(string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler(self::METHOD_PATCH, $path, $callback, $type, $name);
        return $this;
    }

    public function delete(string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler(self::METHOD_DELETE, $path, $callback, $type, $name);
        return $this;
    }

    public function route(string|array $methods, string $path, $callback, $type = "public", string $name = null): BaseRouter
    {
        $this->add_handler($methods, $path, $callback, $type, $name);
        return $this;
    }

    private function add_handler(string|array $method, string $path, $callback, $type, $name): void
    {
        $index = null;
        if (is_array($method)) {
            $index = implode(",", $method);
        }

        if (isset($this->url_prefix)) {
            $path = $this->url_prefix . $path;
        }

        $this->handlers[$index . $path] = [
            'path' => $path,
            'method' => $method,
            'callback' => $callback,
            'type' => $type,
            'name' => $name
        ];
    }

    public function middleware($middleware, array|string $routes): BaseRouter
    {
        $this->add_middleware($middleware, $routes);
        return $this;
    }

    private function add_middleware($middleware, array|string $routes): void
    {
        $index = null;
        if (is_array($routes)) {
            $index = implode(",", $routes);
        }
        $this->middlewares[rand(1, 20) . $index] = [
            'routes' => $routes,
            'middleware' => $middleware
        ];
    }
}