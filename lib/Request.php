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
        return null;
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
}