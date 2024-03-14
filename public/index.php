<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;


$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);


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
