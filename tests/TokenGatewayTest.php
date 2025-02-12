<?php

namespace Omnipay\TwoCheckoutPlus;

use GuzzleHttp\Psr7\Response;
use Omnipay\Tests\GatewayTestCase;

class TokenGatewayTest extends GatewayTestCase
{
    /** @var TokenGateway */
    protected $gateway;
    protected $options;

    public function setUp()
    {
        parent::setUp();

        $mock = $this->getMockClient();
        $body = file_get_contents(__DIR__ . '/Mock/TokenPurchaseSuccess.txt');
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], $body));
        $mock->addResponse($this->getMockHttpResponse('TokenPurchaseFailure.txt'));

        // Add the mock plugin to the client object
        $httpClient = $this->getHttpClient();

        $this->gateway = new TokenGateway($httpClient, $this->getHttpRequest());
        $this->options = [
            'card' => $this->getValidCard(),
            'token' => 'Y2RkZDdjN2EtNjFmZS00ZGYzLWI4NmEtNGZhMjI3NmExMzQ0',
            'transactionId' => '123456',
            'currency' => 'USD',
            'amount' => '20.5'
        ];

        $this->gateway->setAccountNumber('801290261');
        $this->gateway->setTestMode(true);
        $this->gateway->setPrivateKey('5F876A36-D506-4E1F-8EE9-DA2358500F9C');

        $this->gateway->setCart(
            [
                [
                    "name" => "Demo Item",
                    "price" => "4.99",
                    "type" => "product",
                    "quantity" => "1",
                    "recurrence" => "4 Year",
                    "startupFee" => "9.99"
                ],
                [
                    "name" => "Demo Item 2",
                    "price" => "6.99",
                    "type" => "product",
                    "quantity" => "2",
                    "recurrence" => "8 Year",
                    "startupFee" => "19.99"
                ]
            ]
        );
    }

    public function testGateway()
    {
        $this->assertSame('801290261', $this->gateway->getAccountNumber());
        $this->assertSame('5F876A36-D506-4E1F-8EE9-DA2358500F9C', $this->gateway->getPrivateKey());
        $this->assertTrue($this->gateway->getTestMode());

        $cart = $this->gateway->getCart();
        $this->assertCount(2, $cart);
        $this->assertSame('Demo Item', $cart[0]['name']);
        $this->assertSame('Demo Item 2', $cart[1]['name']);
    }


    public function testPurchase()
    {
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getCode());
        $this->assertNull($response->getMessage());
        $this->assertSame('205182114555', $response->getTransactionReference());
        $this->assertSame('123', $response->getTransactionId());

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('602', $response->getCode());
        $this->assertSame('Payment Authorization Failed:  Please verify your Credit Card details are entered correctly and try again, or try another payment method.', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getTransactionId());
    }
}
