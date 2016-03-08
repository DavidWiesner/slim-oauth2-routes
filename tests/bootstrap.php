<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 08.03.16
 * Time: 13:09
 */

require_once __DIR__.'/../vendor/autoload.php';

class BasicSlimTestCase extends \PHPUnit_Framework_TestCase{

	/**
	 * @param $app
	 * @param $path
	 * @param string $method
	 * @param bool $query
	 * @param array $post
	 * @param string $body
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	protected static function runAppRequest($app, $path, $method='GET', $query=false, $post=[], $body='', $contentType='application/x-www-form-urlencoded'){
		$_POST=$post;
		$env=\Slim\Http\Environment::mock(
				[
						'REQUEST_METHOD' => $method,
						'REQUEST_URI' => $path . ($query ? '?'.$query : ''),
						'QUERY_STRING' => $query,
						'CONTENT_TYPE' => $contentType
				]
		);

		$uri = \Slim\Http\Uri::createFromEnvironment($env);
		$headers = \Slim\Http\Headers::createFromEnvironment($env);
		$cookies = [];
		$serverParams = $env->all();
		$reqBody = new \Slim\Http\RequestBody();
		$reqBody->write($body);
		$reqBody->rewind();
		$req = new \Slim\Http\Request($method, $uri, $headers, $cookies, $serverParams, $reqBody);
		$res = new \Slim\Http\Response();
		// Invoke app
		$resOut = $app($req, $res);
		return $resOut;
	}

	protected static function mockTwigApp(){
		$app = new \Slim\App();
		// Fetch DI Container
		$container = $app->getContainer();
		// Twig
		$container['view'] = function ($c) {
			return new \Slim\Views\PhpRenderer(__DIR__.'/../templates/');
		};
		return $app;
	}


}