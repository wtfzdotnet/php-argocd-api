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
        $client = new Client('http://localhost');

        $this->assertInstanceOf(ClientInterface::class, $client->getHttpClient());
    }

    /**
     * @test
     */
    public function shouldPassHttpClientInterfaceToConstructor()
    {
        $httpClientMock = $this->getMockBuilder(ClientInterface::class)
            ->getMock();

        $client = Client::createWithHttpClient($httpClientMock, 'http://localhost');

        $this->assertInstanceOf(ClientInterface::class, $client->getHttpClient());
    }

    /**
     * @test
     */
    public function shouldSetBearerTokenAuthenticationPluginOnAuthenticate()
    {
        $testToken = 'test_token';

        $builder = $this->getMockBuilder(Builder::class)
            ->setMethods(['addPlugin', 'removePlugin'])
            ->disableOriginalConstructor() // Use disableOriginalConstructor if Builder has a complex constructor
            ->getMock();

        // Expect removePlugin to be called to clear any existing auth
        $builder->expects($this->once())
            ->method('removePlugin')
            ->with($this->equalTo(Authentication::class));

        // Expect addPlugin to be called with the new Authentication plugin
        $builder->expects($this->once())
            ->method('addPlugin')
            ->with($this->equalTo(new Authentication($testToken, AuthMethod::BEARER_TOKEN)));

        $client = $this->getMockBuilder(Client::class)
            ->setConstructorArgs(['http://localhost']) // Ensure constructor is called with necessary args
            ->setMethods(['getHttpClientBuilder'])
            ->getMock();

        $client->expects($this->any())
            ->method('getHttpClientBuilder')
            ->willReturn($builder);

        // Mock the SessionService and its create method if client->authenticate directly calls it
        // For this refactoring, we assume authenticate directly configures the builder as per task
        // If authenticate itself makes an API call to validate the token, that would need further mocking.
        // Based on Client::authenticate method, it does try to make a call if password is not null.
        // However, we are calling with only a token.

        $client->authenticate($testToken);
    }

    /**
     * @test
     */
    public function shouldThrowExceptionWhenAuthenticatingWithoutMethodSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new Client('http://localhost');

        $client->authenticate('login', null, null);
    }

    /**
     * @test
     *
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetApiInstance($apiName, $class)
    {
        $client = new Client('http://localhost');

        $this->assertInstanceOf($class, $client->api($apiName));
    }

    /**
     * @test
     *
     * @dataProvider getApiClassesProvider
     */
    public function shouldGetMagicApiInstance($apiName, $class)
    {
        $client = new Client('http://localhost');

        $this->assertInstanceOf($class, $client->$apiName());
    }

    /**
     * @test
     */
    public function shouldNotGetApiInstance()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new Client('http://localhost');
        $client->api('do_not_exist');
    }

    public function getApiClassesProvider()
    {
        return [
            ['session', \ArgoCD\Api\SessionService::class],
            ['sessionservice', \ArgoCD\Api\SessionService::class],
            ['application', \ArgoCD\Api\ApplicationService::class],
            ['applicationservice', \ArgoCD\Api\ApplicationService::class],
            ['account', \ArgoCD\Api\AccountService::class],
            ['accountservice', \ArgoCD\Api\AccountService::class],
        ];
    }
}
