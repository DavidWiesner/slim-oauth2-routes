<?php

namespace Chadicus\Slim\OAuth2\Routes;

use Slim\App;

/**
 * Slim route for oauth2 receive-code.
 */
final class ReceiveCode
{
    const ROUTE = '/receive-code';

    /**
     * The slim framework application instance.
     *
     * @var Slim $slim
     */
    private $slim;

    /**
     * The template for /receive-code
     *
     * @var string
     */
    private $template;

    /**
     * Construct a new instance of ReceiveCode route.
     *
     * @param Slim   $slim     The slim framework application instance.
     * @param string $template The template for /receive-code.
     */
    public function __construct(App $slim, $template = 'receive-code.phtml')
    {
        $this->slim = $slim;
        $this->template = $template;
    }

    /**
     * Call this class as a function.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $res
     * @param \Psr\Http\Message\ResponseInterface $req
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($res, $req)
    {
        return $this->slim->getContainer()['view']->render($req, $this->template, ['code' => $res->getQueryParam('code')]);
    }

    /**
     * Register this route with the given Slim application and OAuth2 server
     *
     * @param App   $slim     The slim framework application instance.
     * @param string $template The template for /receive-code.
     *
     * @return void
     */
    public static function register(App $slim, $template = 'receive-code.phtml')
    {
        $slim->map(['GET', 'POST'], self::ROUTE, new self($slim, $template))->setName('receive-code');
    }
}
