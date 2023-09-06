<?php
declare(strict_types=1);

namespace Lib;

class Request
{
    private $body;

    public $payload;

    public function __construct()
    {

    }

    public function json($key, $default = null)
    {
        $body = json_decode(file_get_contents("php://input"));
        if (!empty($body->{$key})) {
            return $body->{$key};
        } else {
            return $default;
        }
    }


    public function args($key, $default = null)
    {
        if (isset($_GET[$key]) && !empty($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    public function file($key)
    {
        if (isset($_FILES[$key])) {
            return $_FILES[$key];
        }
        return null;
    }

    public function authorization(string $name)
    {
        if ($name == "email") {
            return $_SERVER['PHP_AUTH_USER'] ?? null;
        } elseif ($name == "password") {
            return $_SERVER['PHP_AUTH_PW'] ?? null;
        } else {
            return null;
        }
    }

    public function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public function set_header($header_name, $value)
    {
        header("$header_name: $value");
    }

    public function locals($key, $value = null)
    {
        if (!isset($_SERVER['locals'])) {
            $_SERVER['locals'] = [];
        }

        if (!isset($value)) {
            return $_SERVER['locals'][$key] ?? null;
        } else {
            $_SERVER['locals'][$key] = $value;
        }
    }
}
