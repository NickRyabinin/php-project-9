<?php

// namespace Hexlet\Code;

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

$container = new Container();
$container->set('renderer', function () {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
        return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
        return $this->get('renderer')->render($response, 'main.phtml');
})->setName('main');

/**
 * GET /{file} - Load a static asset.
 *
 * THIS MUST BE PLACED AT THE VERY BOTTOM OF YOUR SLIM APPLICATION, JUST BEFORE
 * $app->run()!!!
 */
$app->get('/{file}', function ($request, $response, $args) {
        $filePath = __DIR__ . '/' . $args['file'];

        if (!file_exists($filePath)) {
            return $response->withStatus(404, 'File Not Found');
        }

        switch (pathinfo($filePath, PATHINFO_EXTENSION)) {
            case 'css':
                $mimeType = 'text/css';
                break;

            case 'js':
                $mimeType = 'application/javascript';
                break;

            default:
                $mimeType = 'text/html';
        }

        $newResponse = $response->withHeader('Content-Type', $mimeType . '; charset=UTF-8');

        $newResponse->getBody()->write(file_get_contents($filePath));

        return $newResponse;
    });

$app->run();
