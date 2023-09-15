<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Flash;
use Carbon\Carbon;
use Valitron\Validator;
use Hexlet\Code\Database;

$container = new Container();
$container->set('renderer', function () {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
        return new \Slim\Flash\Messages();
});
$container->set('pdo', function () {
        $pdo = Database::get()->connect();
        return $pdo;
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
        $this->get('pdo')->exec("CREATE TABLE IF NOT EXISTS urls (
                id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                name varchar(255) NOT NULL UNIQUE,
                created_at timestamp
            );");
        $this->get('pdo')->exec("CREATE TABLE IF NOT EXISTS url_checks (
                id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                url_id bigint REFERENCES urls (id),
                status_code int,
                h1 text,
                title text,
                description text,
                created_at timestamp
            );");
        return $this->get('renderer')->render($response, 'main.phtml');
})->setName('main');

$app->run();
