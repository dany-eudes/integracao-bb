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

    // *** REVERT FIX: TLS 1.2 is now supported by PHP 5.6.40, 
    // we re-enable proper verification logic. ***
    
    // Guzzle 6.x uses 'base_uri' and does not require the 'defaults' wrapper.
    $this->setHttpClient(new Client([
                                      'base_uri' => $this->getUrlApi(), 
                                      'verify'   => $this->isProduction(), // Revert to using the secure production flag
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

    $response = $this->getHttpClient()->post(
      $this->getUrlToken(),
      [
        'headers'     => $headers,
        // *** ADJUSTMENT 1: Use 'form_params' for Guzzle 6 token requests ***
        'form_params' => $body, 
      ]
    );

    // *** ADJUSTMENT 2: Use getContents() for Guzzle 6 body retrieval ***
    return json_decode($response->getBody()->getContents()); 
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

    // *** ADJUSTMENT 3: Use getContents() for Guzzle 6 body retrieval ***
    return json_decode($response->getBody()->getContents());
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
