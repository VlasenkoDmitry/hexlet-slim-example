<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);



$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/user/{nick}', function ($request, $response, $args) {
    $params = [
        'nick' => $args["nick"]
    ];
    return $this->get('renderer')->render($response, 'user/index.phtml', $params);
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam("user");
    $result = array_filter($users, fn($elem) => strpos($elem, $term) !== false);
    $params = [
        'users' => $result,
        'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {
    $id = $args["id"];
    $userArray = [];
    foreach ($users as $user) {
        if ($user["id"] == $id) {
            $userArray[] = $user;
        }
    }
    $params = [
        "users" => $userArray
    ];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});


















$app->get('/', function ($request, $response) {
    return $response->write('go to the /companies');
});

$app->get('/companies', function ($request, $response) {
    return $response->write('companies');
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});


$app->run();
