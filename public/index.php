<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator\Validator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/users', function ($request, $response) {
    $term = $request->getQueryParam("user");
    $result = json_decode(file_get_contents("../usersSaveFile.txt"), true);
    if ($term !== "") {
        $result = array_filter($result, fn($elem) => stripos($elem["name"], $term) === 0);
    }
    $messages = $this->get('flash')->getMessages();
    $params = [
        'users' => $result,
        'term' => $term,
        'messages' => $messages,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName("users");

$app->get('/user/{id}', function ($request, $response, $args) {

    $data = json_decode(file_get_contents("../usersSaveFile.txt"), true);
    $result = array_filter($data, fn($elem) => $elem["id"] == $args["id"]);
    if (count($result) === 0) {
        $params = [
            'status' => "404"
        ];
        return $this->get('renderer')->render($response->withStatus(404), 'user/show.phtml', $params);
    } else {
        $params = [
            'user' => $result
        ];
        return $this->get('renderer')->render($response, 'user/show.phtml', $params);
    }

})->setName("userId");


$app->get('/users/new', function ($request, $response, array $args ) {
    $params = [
        'data' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName("newUsers");


$app->post('/users', function ($request, $response) use ($router) {

    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (!empty($errors)) {
        $params = [
            'data' => $user,
            'errors' => $errors
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
    } else {
        $data = json_decode(file_get_contents("../usersSaveFile.txt"), true);
        if (empty($data)) {
            $id = 1;
        } else {
            $id = count($data) + 1;
        }
        $user["id"] = $id;
        $data[$id] = $user;
        file_put_contents("../usersSaveFile.txt", json_encode($data));
    
        $this->get('flash')->addMessage('success', 'User was created successfuly');
    
        $url = $router->urlFor("users");
        return $response->withRedirect($url);
    }
});


$app->run();







