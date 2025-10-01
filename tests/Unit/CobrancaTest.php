<?php

use insign\BB\Cobranca;

class CobrancaTest extends \PHPUnit_Framework_TestCase
{
  protected $cobranca;
  protected $cobrancaProd;

  // Use setUp() instead of Pest's beforeEach()
  public function setUp()
  {
    // Note: The original used named arguments (production: false), 
    // which is a PHP 8.0+ feature, so we use positional arguments here.
    $this->cobranca = new Cobranca('clientId', 'clientSecret', 'developerKey', FALSE);
    $this->cobrancaProd = new Cobranca('clientId', 'clientSecret', 'developerKey', TRUE);
  }

  // Replaces: test('verifica se a URL do token está correta em ambiente sandbox', function () { ...
  public function testUrlTokenSandbox()
  {
    $this->assertEquals('https://oauth.sandbox.bb.com.br/oauth/token', $this->cobranca->getUrlToken());
  }

  // Replaces: test('verifica se a URL do token está correta em ambiente produção', function () { ...
  public function testUrlTokenProduction()
  {
    $this->assertEquals('https://oauth.bb.com.br/oauth/token', $this->cobrancaProd->getUrlToken());
  }

  // Replaces: test('verifica se a URL da API está correta em ambiente sandbox', function () { ...
  public function testUrlApiSandbox()
  {
    $this->assertEquals('https://api.sandbox.bb.com.br/', $this->cobranca->getUrlApi());
  }

  // Replaces: test('verifica se a URL da API está correta em ambiente produção', function () { ...
  public function testUrlApiProduction()
  {
    $this->assertEquals('https://api.bb.com.br/', $this->cobrancaProd->getUrlApi());
  }

  // Replaces: test('verifica se a hash básica é gerada corretamente', function () { ...
  public function testBasicHashIsGeneratedCorrectly()
  {
    $expectedHash = base64_encode('clientId:clientSecret');
    $this->assertEquals($expectedHash, $this->cobranca->getBasicHash());
  }

  // Replaces: test('verifica se o modo produção está desativado por padrão', function () { ...
  public function testProductionModeIsDisabledByDefault()
  {
    $this->assertFalse($this->cobranca->isProduction());
  }

  // Replaces: test('verifica se o modo produção está habilitado', function () { ...
  public function testProductionModeIsEnabled()
  {
    $this->assertTrue($this->cobrancaProd->isProduction());
  }
}
