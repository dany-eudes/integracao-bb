<?php

namespace insign\BB;

use stdClass;
use GuzzleHttp\Client;
// use Psr\Http\Message\ResponseInterface; // Removed type hint

class Cobranca
{
  protected $httpClient;
  protected $clientID;
  protected $clientSecret;
  protected $developerKey;
  protected $production;

  public function __construct(
    $clientID,
    $clientSecret,
    $developerKey,
    $production = FALSE
  )
  {
    $this->clientID = $clientID;
    $this->clientSecret = $clientSecret;
    $this->developerKey = $developerKey;
    $this->production = $production;

    // Updated for Guzzle 5.x compatibility (base_url and defaults)
    $this->setHttpClient(new Client([
                                      'base_url' => $this->getUrlApi(), 
                                      'defaults' => [
                                          'verify'   => $this->isProduction(),
                                      ],
                                    ]));

  }

  public function getUrlToken()
  {
    return "https://oauth." . ($this->isProduction() ? '' : 'sandbox.') . "bb.com.br/oauth/token";
  }

  public function getUrlApi()
  {
    return "https://api." . ($this->isProduction() ? '' : 'sandbox.') . "bb.com.br/";
  }

  public function getTokenAccess()
  {
    $headers = [
      "Content-Type"  => "application/x-www-form-urlencoded",
      "Authorization" => "Basic " . $this->getBasicHash(),
    ];

    $body = [
      'grant_type' => "client_credentials",
      'scope'      => "cobrancas.boletos-info cobrancas.boletos-requisicao",
    ];

    // Converted form_params to a raw body string for better Guzzle 5 compatibility
    $response = $this->getHttpClient()->post(
      $this->getUrlToken(),
      [
        'headers'     => $headers,
        'body' => http_build_query($body),
      ]
    );

    // Removed getContents() to rely on the Guzzle 5.x response body object
    return json_decode($response->getBody());
  }

  public function registrarBoleto(array $campos)
  {
    // Removed named arguments (uri:, options:)
    $response = $this->getHttpClient()->post(
      "cobrancas/v2/boletos?gw-dev-app-key={$this->developerKey}",
      [
        "headers" => $this->getAuthHeaders(),
        "json"    => $campos,
      ]
    );

    return $this->processAnswer($response);
  }

  public function alterarBoleto($id, array $campos)
  {
    $response = $this->getHttpClient()->patch(
      "cobrancas/v2/boletos/{$id}?gw-dev-app-key={$this->developerKey}",
      [
        "headers" => $this->getAuthHeaders(),
        "json"    => $campos,
      ]
    );

    return $this->processAnswer($response);
  }

  public function verBoleto($id, $convenio)
  {
    $response = $this->getHttpClient()->get(
      "cobrancas/v2/boletos/{$id}?gw-dev-app-key={$this->developerKey}&numeroConvenio={$convenio}",
      [
        "headers" => $this->getAuthHeaders(),
      ]
    );

    return $this->processAnswer($response);
  }

  public function baixarBoleto($id, $convenio)
  {
    $response = $this->getHttpClient()->post(
      "cobrancas/v2/boletos/{$id}/baixar?gw-dev-app-key={$this->developerKey}",
      [
        "headers" => $this->getAuthHeaders(),
        "json"    => ["numeroConvenio" => $convenio],
      ]
    );

    // Removed getContents() to rely on the Guzzle 5.x response body object
    return json_decode($response->getBody());
  }

  public function setProduction($production)
  {
    $this->production = $production;
  }

  public function isProduction()
  {
    return $this->production;
  }

  public function getBasicHash()
  {
    return base64_encode("{$this->clientID}:{$this->clientSecret}");
  }

  public function getAuthHeaders()
  {
    return [
      "Authorization"               => "Bearer " . $this->getTokenAccess()->access_token,
      "Content-Type"                => "application/json",
      "X-Developer-Application-Key" => $this->developerKey,
    ];
  }

  public function processAnswer($response)
  {
    // Simplified for PHP 5.4.9 (removed JSON_THROW_ON_ERROR and extra args)
    return json_decode($response->getBody()->getContents());
  }

  public function getHttpClient()
  {
    return $this->httpClient;
  }

  public function setHttpClient($httpClient)
  {
    $this->httpClient = $httpClient;
  }

}
