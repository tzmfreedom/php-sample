<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class HogeAction
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        return $response;
    }
}

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/hoge/{id}', HogeAction::class);
        $group->get('/fuga/{id}', HogeAction::class);
    })->add(function (Request $request, RequestHandler $handler) {
        $request = $request->withAttribute('api', []);
        return $handler->handle($request);
    });
    $app->add(function (Request $request, RequestHandler $handler) {
        $request = $request->withAttribute('application', []);
        return $handler->handle($request);
    });
};
