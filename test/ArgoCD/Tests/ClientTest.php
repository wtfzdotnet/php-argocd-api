<?php

namespace ArgoCD\Tests;

use ArgoCD\Api;
use ArgoCD\AuthMethod;
use ArgoCD\Client;
use ArgoCD\Exception\BadMethodCallException;
use ArgoCD\Exception\InvalidArgumentException;
use ArgoCD\HttpClient\Builder;
use ArgoCD\HttpClient\Plugin\Authentication;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldNotHaveToPassHttpClientToConstructor()
    {
        $client = new Client();

        $this->assertInstanceOf(ClientInterface::class, $client->getHttpClient());
    }

    /**
     * @test
     */
    public function shouldPassHttpClientInterfaceToConstructor()
    {
        $httpClientMock = $this->getMockBuilder(ClientInterface::class)
            ->getMock();

        $client = Client::createWithHttpClient($httpClientMock);

        $this->assertInstanceOf(ClientInterface::class, $client->getHttpClient());
    }

    /**
     * @test
     *
     * @dataProvider getAuthenticationFullData
     */
    public function shouldAuthenticateUsingAllGivenParameters($login, $password, $method)
    {
        $builder = $this->getMockBuilder(Builder::class)
            ->setMethods(['addPlugin', 'removePlugin'])
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())
            ->method('addPlugin')
            ->with($this->equalTo(new Authentication($login, $password, $method)));
        $builder->expects($this->once())
            ->method('removePlugin')
            ->with(Authentication::class);

        $client = $this->getMockBuilder(\ArgoCD\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHttpClientBuilder'])
            ->getMock();
        $client->expects($this->any())
            ->method('getHttpClientBuilder')
            ->willReturn($builder);

        $client->authenticate($login, $password, $method);
    }

    public function getAuthenticationFullData()
    {
        return [
            ['token', null, AuthMethod::ACCESS_TOKEN],
            ['client_id', 'client_secret', AuthMethod::CLIENT_ID],
            ['token', null, AuthMethod::JWT],
        ];
    }

    /**
     * @test
     */
    public function shouldAuthenticateUsingGivenParameters()
    {
        $builder = $this->getMockBuilder(Builder::class)
            ->setMethods(['addPlugin', 'removePlugin'])
            ->getMock();
        $builder->expects($this->once())
            ->method('addPlugin')
            ->with($this->equalTo(new Authentication('token', null, AuthMethod::ACCESS_TOKEN)));

        $builder->expects($this->once())
            ->method('removePlugin')
            ->with(Authentication::class);

        $client = $this->getMockBuilder(\ArgoCD\Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHttpClientBuilder'])
            ->getMock();
        $client->expects($this->any())
            ->method('getHttpClientBuilder')
            ->willReturn($builder);

        $client->authenticate('token', AuthMethod::ACCESS_TOKEN);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenAuthenticatingWithoutMethodSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new Client();

        $client->authenticate('login', null, null);
    }

    /**
     * @test
     *
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetApiInstance($apiName, $class)
    {
        $client = new Client();

        $this->assertInstanceOf($class, $client->api($apiName));
    }

    /**
     * @test
     *
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetMagicApiInstance($apiName, $class)
    {
        $client = new Client();

        $this->assertInstanceOf($class, $client->$apiName());
    }

    /**
     * @test
     */
    public function shouldNotGetApiInstance()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new Client();
        $client->api('do_not_exist');
    }
}
