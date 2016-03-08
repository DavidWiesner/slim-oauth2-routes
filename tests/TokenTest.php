<?php

namespace ChadicusTest\Slim\OAuth2\Routes;

use Chadicus\Slim\OAuth2\Routes\Token;

/**
 * Unit tests for the \Chadicus\Slim\OAuth2\Routes\Token class.
 *
 * @coversDefaultClass \Chadicus\Slim\OAuth2\Routes\Token
 * @covers ::<private>
 * @covers ::__construct
 */
final class TokenTest extends \BasicSlimTestCase
{
    /**
     * Verify basic behavior of __invoke()
     *
     * @test
     * @covers ::__invoke
     *
     * @return void
     */
    public function invoke()
    {
        $storage = new \OAuth2\Storage\Memory(
            [
                'client_credentials' => [
                    'testClientId' => [
                        'client_id' => 'testClientId',
                        'client_secret' => 'testClientSecret',
                    ],
                ],
            ]
        );

        $server = new \OAuth2\Server(
            $storage,
            [
                'access_lifetime' => 3600,
            ],
            [
                new \OAuth2\GrantType\ClientCredentials($storage),
            ]
        );

        $json = json_encode(
            [
                'client_id' => 'testClientId',
                'client_secret' => 'testClientSecret',
                'grant_type' => 'client_credentials',
            ]
        );

        $contentType = 'application/json';
        $path = '/token';

        $slim = new \Slim\App();
        $slim->post($path, new Token($slim, $server));
        $response=self::runAppRequest($slim, $path,'POST',false,[], $json, $contentType);
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $actual = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(
            [
                'access_token' => $actual['access_token'],
                'expires_in' => 3600,
                'token_type' => 'Bearer',
                'scope' => null,
            ],
            $actual
        );
    }

    /**
     * Verify basic behavior of register
     *
     * @test
     * @covers ::register
     *
     * @return void
     */
    public function register()
    {
        $storage = new \OAuth2\Storage\Memory([]);
        $server = new \OAuth2\Server($storage, [], []);

        $slim = new \Slim\App();

        Token::register($slim, $server);

        $route = $slim->getContainer()['router']->getNamedRoute('token');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertInstanceOf('\Chadicus\Slim\OAuth2\Routes\Token', $route->getCallable());
        $this->assertSame(['POST'], $route->getMethods());
    }
}
