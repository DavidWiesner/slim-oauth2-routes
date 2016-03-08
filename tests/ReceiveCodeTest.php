<?php

namespace ChadicusTest\Slim\OAuth2\Routes;

use Chadicus\Slim\OAuth2\Routes\ReceiveCode;

/**
 * Unit tests for the \Chadicus\Slim\OAuth2\Routes\ReceiveCode class.
 *
 * @coversDefaultClass \Chadicus\Slim\OAuth2\Routes\ReceiveCode
 * @covers ::<private>
 * @covers ::__construct
 */
final class ReceiveCodeTest extends \BasicSlimTestCase
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
                        'redirect_uri' => '/receive-code',
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

        $code = md5(time());

        $query = "code={$code}&state=xyz";
        $contentType = 'application/json';
        $path = '/receive-code';

        $slim = self::mockTwigApp();
        $slim->post('/receive-code', new ReceiveCode($slim));
        $response = self::runAppRequest($slim, $path, 'POST', $query, [], '', $contentType);

        $this->assertSame(200, $response->getStatusCode());

        $expected = <<<HTML
<h2>The authorization code is {$code}</h2>

HTML;
        $response->getBody()->rewind();

        $this->assertSame($expected, $response->getBody()->getContents());
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

        ReceiveCode::register($slim);

        $route = $slim->getContainer()['router']->getNamedRoute('receive-code');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertInstanceOf('\Chadicus\Slim\OAuth2\Routes\ReceiveCode', $route->getCallable());
        $this->assertSame(
            ['GET', 'POST'],
            $route->getMethods()
        );
    }
}
