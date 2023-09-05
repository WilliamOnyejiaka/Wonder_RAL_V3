<?php
declare(strict_types=1);
namespace Lib;

ini_set('display_errors', 1);
use Lib\Controller;
use Lib\Response;
use Lib\Request;

class Router
{

  private array $handlers;

  private array $middlewares;
  private const METHOD_GET = "GET";
  private const METHOD_POST = "POST";
  private const METHOD_PUT = "PUT";
  private const METHOD_PATCH = "PATCH";
  private const METHOD_DELETE = "DELETE";
  private $callback_404;
  private $callback_405;
  private string $uri_path_start;
  private $allow_cors;
  private $token_configs;

  public function __construct($uri_path_start, $allow_cors, $token_configs = null)
  {
    $this->allow_cors = $allow_cors;
    $this->uri_path_start = $uri_path_start;
    $this->token_configs = $token_configs;
  }

  public function get(string $path, $callback, $type = "public", string $name = null): Router
  {
    $this->add_handler(self::METHOD_GET, $path, $callback, $type, $name);
    return $this;
  }

  public function post(string $path, $callback, $type = "public", string $name = null): Router
  {
    $this->add_handler(self::METHOD_POST, $path, $callback, $type, $name);
    return $this;
  }

  public function put(string $path, $callback, $type = "public", string $name = null): Router
  {
    $this->add_handler(self::METHOD_PUT, $path, $callback, $type, $name);
    return $this;
  }

  public function patch(string $path, $callback, $type = "public", string $name = null): Router
  {
    $this->add_handler(self::METHOD_PATCH, $path, $callback, $type, $name);
    return $this;
  }

  public function delete(string $path, $callback, $type = "public", string $name = null): Router
  {
    $this->add_handler(self::METHOD_DELETE, $path, $callback, $type, $name);
    return $this;
  }

  public function route(string|array $methods, string $path, $callback, $type = "public", string $name = null): Router
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
    $this->handlers[$index . $path] = [
      'path' => $path,
      'method' => $method,
      'callback' => $callback,
      'type' => $type,
      'name' => $name
    ];
  }

  public function middleware($middleware, array|string $routes): Router
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

  private function activate_cors()
  {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header("Access-Control-Allow-Credentials: true");
    header('Content-Type: application/json');


    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == "OPTIONS") {
      header('Access-Control-Allow-Origin: *');
      header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
      header("HTTP/1.1 200 OK");
      die();
    }
  }

  public function add_405_callback($callback)
  {
    $this->callback_405 = $callback;
  }

  private function get_request_path(): string
  {
    $request_uri = parse_url($_SERVER['REQUEST_URI']);
    $paths = explode("/", $request_uri['path']);
    $requestPath = "";
    $start_index = null;
    for ($i = 0; $i < count($paths); $i++) {
      if ($paths[$i] === $this->uri_path_start) {
        $start_index = $i + 1;
        break;
      }
    }

    if (!$start_index) {
      http_response_code(404);
      echo json_encode([
        'error' => true,
        'message' => "starting path not found"
      ]);
      exit();
    }
    for ($i = $start_index; $i < count($paths); $i++) {
      $requestPath = $requestPath . "/" . $paths[$i];
    }
    return $requestPath;
  }

  public function add_404_callback($callback)
  {
    $this->callback_404 = $callback;
  }

  private function invoke_middleware($callback)
  {
    if (is_array($callback)) {
      $classname = $callback[0];
      $class = new $classname;

      $method = $callback[1];
      $callback = [$class, $method];
    }

    $response = new Response();
    $request = new Request();
    call_user_func_array($callback, [$request, $response]);
  }

  private function invoke_callback($callback, $type)
  {
    if (!$callback) {
      header("HTTP/1.0 404 Not Found");
      if (!empty($this->callback_404)) {
        $callback = $this->callback_404;
      } else {
        $callback = function () {
          http_response_code(404);
          echo json_encode([
            'error' => true,
            'message' => "the requested url cannot be found"
          ]);
        };
      }
    }
    if (is_string($callback)) {
      $parts = explode('::', $callback);
      if (is_array($parts)) {
        $classname = array_shift($parts);
        $class = new $classname;

        $method = array_shift($parts);
        $callback = [$class, $method];
      }
    }

    $controller = new Controller($this->token_configs);
    if ($type == "public") {
      $controller->public_controller($callback);
    } elseif ($type == "protected") {
      $controller->protected_controller($callback);
    } elseif ($type == "token") {
      $controller->access_token_controller($callback);
    } else {
      $controller->public_controller(function ($request, $response) {
        $response->send_response(500, [
          'error' => true,
          'message' => "invalid type,only protected or public needed"
        ]);
      });
    }
  }

  private function invoke_405()
  {
    if (!empty($this->callback_405)) {
      return $this->callback_405;
    } else {
      return function () {
        http_response_code(405);
        echo json_encode([
          'error' => true,
          'message' => "method not allowed"
        ]);
      };
    }
  }

  public function run(): void
  {
    $request_path = $this->get_request_path();
    $callback = null;
    $method = $_SERVER['REQUEST_METHOD'];

    foreach ($this->handlers as $handler) {
      if (is_array($handler['method'])) {
        $methods = [];

        foreach ($handler['method'] as $handler_method) {
          array_push($methods, strtoupper($handler_method));
        }
        $handler['method'] = $methods;

        if (in_array($method, $handler['method']) && $handler['path'] === $request_path) {
          $callback = $handler['callback'];
          break;
        }

        if (!in_array($method, $handler['method']) && $handler['path'] === $request_path) {
          $callback = $this->invoke_405();
        }
      }

      if ($method === $handler['method'] && $handler['path'] === $request_path) {
        $callback = $handler['callback'];
        break;
      }

      if ($handler['method'] !== $method && $handler['path'] === $request_path) {
        $callback = $this->invoke_405();
      }
    }

    ($this->allow_cors && $this->activate_cors());

    if (isset($this->middlewares)) {
      foreach ($this->middlewares as $middleware) {
        $routes = $middleware['routes'];
        if (is_array($routes)) {
          if (in_array($handler['name'], $middleware['routes'])) {
            $this->invoke_middleware($middleware['middleware']);
          }
        } else {
          if ($handler['name'] == $middleware['routes']) {
            $this->invoke_middleware($middleware['middleware']);
          }
        }

      }
    }

    $this->invoke_callback($callback, $handler['type']);
  }
}