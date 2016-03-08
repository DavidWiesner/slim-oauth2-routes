<?php

namespace Chadicus\Slim\OAuth2\Routes;

use Chadicus\Slim\OAuth2\Http\MessageBridge;
use OAuth2;
use Slim\App;
use Slim\Slim;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Slim route for /authorization endpoint.
 */
class Authorize
{
    const ROUTE = '/authorize';

    /**
     * The slim framework application instance.
     *
     * @var Slim
     */
    private $slim;

    /**
     * The oauth2 server imstance.
     *
     * @var OAuth2\Server
     */
    private $server;

    /**
     * The template for /authorize
     *
     * @var string
     */
    private $template;

    /**
     * Construct a new instance of Authorize.
     *
     * @param Slim          $slim     The slim framework application instance.
     * @param OAuth2\Server $server   The oauth2 server imstance.
     * @param string        $template The template for /authorize.
     */
    public function __construct(App $slim, OAuth2\Server $server, $template = 'authorize.phtml')
    {
        $this->slim = $slim;
        $this->server = $server;
        $this->template = $template;
    }

    /**
     * Call this class as a function.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $slimRequest
     * @param \Psr\Http\Message\ResponseInterface $slimResponse
     * @param $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($slimRequest, $slimResponse, $next)
    {
        $request = MessageBridge::newOAuth2Request($slimRequest);
        $response = new OAuth2\Response();
        $isValid = $this->server->validateAuthorizeRequest($request, $response);
        if (!$isValid) {
            MessageBridge::mapResponse($response, $slimResponse);
            return $slimResponse;
        }

        $parsedBody = $slimRequest->getParsedBody();
        $authorized = is_array($parsedBody) && isset($parsedBody['authorized']) ? $parsedBody['authorized'] : '';
        if (empty($authorized)) {
            $this->slim->getContainer()['view']->render($slimResponse, $this->template, ['client_id' => $request->query('client_id', false)]);
            return $slimResponse;
        }

        $this->server->handleAuthorizeRequest($request, $response, $authorized === 'yes');

        MessageBridge::mapResponse($response, $slimResponse);
        return $slimResponse;
    }

    /**
     * Register this route with the given Slim application and OAuth2 server
     *
     * @param Slim          $slim     The slim framework application instance.
     * @param OAuth2\Server $server   The oauth2 server imstance.
     * @param string        $template The template for /authorize.
     *
     * @return void
     */
    public static function register(App $slim, OAuth2\Server $server, $template = 'authorize.phtml')
    {
        $slim->map(['GET', 'POST'], self::ROUTE, new self($slim, $server, $template))->setName('authorize');
    }
}
