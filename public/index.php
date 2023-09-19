<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Flash;
use Carbon\Carbon;
use Valitron\Validator;
use Hexlet\Code\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

session_start();

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
$container->set('client', function () {
    return new Client();
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

$app->post('/urls', function ($request, $response) use ($router) {
    $rawData = (array)$request->getParsedBody();
    $v = new Validator($rawData['url']);
    $v->rule('required', 'name')->message('URL не должен быть пустым')
        ->rule('url', 'name')->message('Некорректный URL')
        ->rule('lengthMax', 'name', 255)->message('Некорректный URL');

    if (!($v->validate())) {
        $errors = $v->errors();
        $params = [
            'url' => $rawData['url'],
            'errors' => $errors,
            'isInvalid' => 'is-invalid',
        ];

        return $this->get('renderer')->render($response->withStatus(422), 'main.phtml', $params);
    }

    try {
        $pdo = $this->get('pdo');

        $urlString = strtolower($rawData['url']['name']);
        $parsedUrl = parse_url($urlString);
        $name = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
        $createdAt = Carbon::now();

        $query = "SELECT name FROM urls WHERE name = '{$name}'";
        $existedUrl = $pdo->query($query)->fetchAll();

        if (count($existedUrl) > 0) {
            $query = "SELECT id FROM urls WHERE name = '{$name}'";
            $existedUrlId = (string)($pdo->query($query)->fetchColumn());

            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($router->urlFor('show', ['id' => $existedUrlId]));
        }

        $query = "INSERT INTO urls (name, created_at) VALUES ('{$name}', '{$createdAt}')";
        $pdo->exec($query);
        $lastId = $pdo->lastInsertId();

        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $response->withRedirect($router->urlFor('show', ['id' => $lastId]));
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }
});

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) {
    $flash = $this->get('flash')->getMessages();
    $alert = key($flash);
    if ($alert === 'error') {
        $alert = 'warning';
    }

    $pdo = $this->get('pdo');
    $query = "SELECT * FROM urls WHERE id = {$args['id']}";
    $currentPage = $pdo->query($query)->fetch();

    if ($currentPage) {
        $query = "SELECT * FROM url_checks WHERE url_id = {$args['id']} ORDER BY created_at DESC";
        $checks = $pdo->query($query)->fetchAll();
        $params = [
            'flash' => $flash,
            'alert' => $alert,
            'page' => $currentPage,
            'checks' => $checks,
        ];

        return $this->get('renderer')->render($response, 'show.phtml', $params);
    }
    return $response->getBody()->write("Произошла ошибка при проверке, не удалось подключиться")->withStatus(404);
})->setName('show');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    $urlId = $args['url_id'];

    try {
        $pdo = $this->get('pdo');
        $query = "SELECT name FROM urls WHERE id = {$urlId}";
        $urlToCheck = $pdo->query($query)->fetchColumn();

        $createdAt = Carbon::now();

        $client = $this->get('client');
        try {
            $res = $client->get($urlToCheck);
            $statusCode = $res->getStatusCode();
            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (TransferException $e) {
            $this->get('flash')->addMessage('warning', 'Произошла ошибка при проверке');
            return $response->withRedirect($router->urlFor('show', ['id' => $urlId]));
        }

        $query = "INSERT INTO url_checks (
            url_id,
            created_at,
            status_code)
            VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$urlId, $createdAt, $statusCode]);
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }

    return $response->withRedirect($router->urlFor('show', ['id' => $urlId]));
});

$app->get('/urls', function ($request, $response) {
    $pdo = $this->get('pdo');
    $query = 'SELECT id, name FROM urls ORDER BY created_at DESC';
    $urlsToShow = $pdo->query($query)->fetchAll(\PDO::FETCH_UNIQUE);

    return $this->get('renderer')->render($response, 'list.phtml', ['urls' => $urlsToShow]);
})->setName('list');

$app->run();
