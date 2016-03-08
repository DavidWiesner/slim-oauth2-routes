<?php

namespace ChadicusTest\Slim\OAuth2\Routes;

use Chadicus\Slim\OAuth2\Routes\Authorize;


/**
 * Unit tests for the \Chadicus\Slim\OAuth2\Routes\Authorize class.
 *
 * @coversDefaultClass \Chadicus\Slim\OAuth2\Routes\Authorize
 * @covers ::<private>
 * @covers ::__construct
 */
final class AuthorizeTest extends \BasicSlimTestCase
{
    /**
     * Verify behavior of __invoke() with no client_id parameter
     *
     * @test
     * @covers ::__invoke
     *
     * @return void
     */
    public function invokeNoClientSpecified()
    {
        $storage = new \OAuth2\Storage\Memory([]);
        $server = new \OAuth2\Server($storage, [], []);
        $slim = new \Slim\App();
        $slim->get('/authorize', function($request, $response, $args){
            return $response->getBody()->write('helloworld');
        })->add(new Authorize($slim, $server));

        $response = $this->runAppRequest($slim, '/authorize');

        $this->assertSame(400, $response->getStatusCode());

        $actual = json_decode($response->getBody(), true);
        $this->assertSame(
            [
                'error' => 'invalid_client',
                'error_description' => 'No client id supplied',
            ],
            $actual
        );
    }

    /**
     * Verify behavior of __invoke() with invalid client_id parameter
     *
     * @test
     * @covers ::__invoke
     *
     * @return void
     */
    public function invokeInvalidClientSpecified()
    {
        $storage = new \OAuth2\Storage\Memory([]);
        $server = new \OAuth2\Server($storage, [], []);

        $query = 'client_id=invalidClientId';
        $slim = new \Slim\App();
        $slim->get('/authorize', new Authorize($slim, $server));
        $response=$this->runAppRequest($slim, '/authorize', 'GET', $query);

        $this->assertSame(400, $response->getStatusCode());

        $actual = json_decode($response->getBody()->getContents(), true);
        $this->assertSame(
            [
                'error' => 'invalid_client',
                'error_description' => 'The client id supplied is invalid',
            ],
            $actual
        );
    }

    /**
     * Verify basic behavior of __invoke().
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
                'allow_implicit' => true,
            ],
            []
        );

        $path = '/authorize';
        $query = 'client_id=testClientId&redirect_uri=http://example.com&response_type=code&'
                . 'state=test';
        $body = 'authorized=yes';
        \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
                'PATH_INFO' => $path,
                'QUERY_STRING' => $query,
                'slim.input' => $body,
            ]
        );

        $slim = new \Slim\App();
        $slim->map(['POST', 'GET'], '/authorize', function(){
            echo('hello');
        })->add(new Authorize($slim, $server));

        $response=$this->runAppRequest($slim, $path, 'POST', $query, [], $body);

        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeader('Location')[0];
        $parts = parse_url($location);
        parse_str($parts['query'], $query);

        $this->assertTrue(isset($query['code']));
        $this->assertSame('test', $query['state']);

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

        \Slim\Http\Environment::mock();

        $slim = new \Slim\App();
        Authorize::register($slim, $server);
        $router = $slim->getContainer()->get('router');

        $route = $router->getNamedRoute('authorize');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertInstanceOf('\Chadicus\Slim\OAuth2\Routes\Authorize', $route->getCallable());
        $this->assertSame(['GET', 'POST'], $route->getMethods());
    }

    /**
     * Verify bahavior of /authorize route when authorized parameter is empty.
     *
     * @test
     * @covers ::__invoke
     *
     * @return void
     */
    public function invokeEmptyAuthorized()
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
        $server = new \OAuth2\Server($storage, [], []);

        $query = 'client_id=testClientId&redirect_uri=http://example.com&response_type=code'
                . '&state=test';
        $path = '/authorize';
        $slim=self::mockTwigApp();
        $slim->get('/authorize', new Authorize($slim, $server));

        $response=$this->runAppRequest($slim, $path, 'GET', $query);
	    $response->getBody()->rewind();

        $expected = <<<HTML
<form method="post">
    <label>Do You Authorize testClientId?</label><br />
    <input type="submit" name="authorized" value="yes">
    <input type="submit" name="authorized" value="no">
</form>

HTML;

        $this->assertSame($expected, $response->getBody()->getContents());
    }
}
