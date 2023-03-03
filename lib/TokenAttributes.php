<?php

namespace Lib;

class TokenAttributes
{

  public $access_token_exp_time;
  public $refresh_token_exp_time;
  private $data;
  private $access_token_aud;

  public function __construct($data, $access_token_aud = "users", $exp_time = array())
  {
    $this->access_token_exp_time = isset($exp_time['access_token_exp_time']) ? $exp_time['access_token_exp_time'] : 3600;
    $this->refresh_token_exp_time = isset($exp_time['refresh_token_exp_time']) ? $exp_time['refresh_token_exp_time'] : 2592000;
    $this->access_token_aud = $access_token_aud;
    $this->data = $data;
  }

  public function serialize()
  {
    $needed_values = $this->data['needed_values'];
    $user_data = $this->data['data'];
    $serialized_data = array();
    foreach ($needed_values as $key) {
      $serialized_data[$key] = $user_data[$key];
    }
    return $serialized_data;
  }

  public function access_token_payload()
  {
    $iat = time();
    $nbf = $iat;
    $exp = $iat + $this->access_token_exp_time;
    $aud = $this->access_token_aud;
    $user_data = $this->serialize();

    $payload = array(
      'iat' => $iat,
      'nbf' => $nbf,
      'exp' => $exp,
      'aud' => $aud,
      'data' => $user_data
    );
    return $payload;
  }

  public function refresh_token_payload()
  {
    $iat = time();
    $nbf = $iat;
    $exp = $iat + $this->refresh_token_exp_time;
    $aud = "access_token";
    $user_data = $this->serialize();

    $payload = array(
      'iat' => $iat,
      'nbf' => $nbf,
      'exp' => $exp,
      'aud' => $aud,
      'data' => $user_data
    );
    return $payload;
  }
}
?>