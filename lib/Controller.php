<?php
declare(strict_types=1);

namespace Lib;

require __DIR__ . "/../vendor/autoload.php";

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Lib\Response;
use Lib\Validator;

ini_set("display_errors", 1);

class Controller
{

  private Response $response;
  private Validator $validator;
  private $token_configs;

  public function __construct($token_configs = null)
  {
    $this->response = new Response();
    $this->validator = new Validator();
    $this->token_configs = $token_configs;
  }

  public static function get_jwt(string $token)
  {
    $check_token = preg_match('/Bearer\s(\S+)/', $token, $matches);
    return $check_token == 0 ? false : $matches[1];
  }

  private function get_payload(string $jwt)
  {
    $payload = null;

    if (!$this->token_configs) {
      $this->response->send_response(500, [
        'error' => true,
        'message' => "token_configs is null"
      ]);
    }

    $secret_key = $this->token_configs['secret_key'];
    $hash = $this->token_configs['hash'];

    try {
      $payload = (JWT::decode($jwt, new Key($secret_key, $hash)));
    } catch (\Firebase\JWT\ExpiredException $ex) {
      (new Response())->send_response(400, [
        'error' => true,
        'message' => $ex->getMessage()
      ]);
      exit();
    }
    return $payload;
  }

  public function protected_controller($callback): void
  {
    header('Content-Type: application/json');

    $token = (getallheaders())['Authorization'] ?? false;
    $body = json_decode(file_get_contents("php://input"));

    if ($token) {

      $jwt = $this->validator->get_jwt($token);

      if ($jwt) {
        $payload = $this->get_payload($jwt);
        if ($payload->aud !== "access_token") {
          $callback($payload, $body, $this->response);
        } else {
          $this->response->send_response(400, [
            'error' => true,
            'message' => 'access token needed'
          ]);
        }
      } else {
        $this->response->send_response(400, [
          'error' => true,
          'message' => "invalid jwt"
        ]);
      }
    } else {
      $this->response->send_response(401, [
        'error' => true,
        'message' => "Authorization header missing"
      ]);
    }
  }

  public function public_controller($callback): void
  {
    header('Content-Type: application/json');

    $body = json_decode(file_get_contents("php://input"));
    $callback($body, $this->response);
  }

  public function access_token_controller($callback)
  {
    header('Content-Type: application/json');

    $token = (getallheaders())['Authorization'] ?? false;
    $body = json_decode(file_get_contents("php://input"));

    if ($token) {

      $jwt = $this->validator->get_jwt($token);

      if ($jwt) {
        $payload = $this->get_payload($jwt);
        if ($payload->aud == "access_token") {
          $callback($payload, $body, $this->response);
        } else {
          $this->response->send_response(400, [
            'error' => true,
            'message' => "refresh token needed"
          ]);
        }
      } else {
        $this->response->send_response(400, [
          'error' => true,
          'message' => "invalid jwt"
        ]);
      }
    } else {
      $this->response->send_response(401, [
        'error' => true,
        'message' => "Authorization header missing"
      ]);
    }
  }
}