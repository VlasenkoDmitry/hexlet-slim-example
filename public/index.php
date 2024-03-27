<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator\Validator;
use Slim\Middleware\MethodOverrideMiddleware;

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
$app->add(MethodOverrideMiddleware::class);

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
    }

    $params = [
        'user' => $result
    ];
    return $this->get('renderer')->render($response, 'user/show.phtml', $params);

})->setName("userId");



$app->get('/users/new', function ($request, $response, array $args ) {
    $params = [
        'data' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName("newUsers");



// save new user
$app->post('/users', function ($request, $response) use ($router) {

    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (!empty($errors)) {
        $params = [
            'user' => $user,
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
})->setName("postUsers");

$app->patch('/user/{id}', function($request, $response, $args) use ($router) {


    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $data = json_decode(file_get_contents("../usersSaveFile.txt"), true);

        $id = $args['id'];
        $newData = [];
        foreach ($data as $key => $elem) {
            if ($elem['id'] == $id ) {
                $newData[$key] = ['name' => $user['name'], 'email' => $user['email'], 'id' => $id];
            } else {
                $newData[$key] = $elem;
            }
        }

        file_put_contents("../usersSaveFile.txt", json_encode($newData));    
        $url = $router->urlFor("users");
        return $response->withRedirect($url);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), $router->urlFor('userIdEdit'), $params);
});

$app->get('/user/{id}/edit', function($request, $response, $args) {

    $data = json_decode(file_get_contents("../usersSaveFile.txt"), true);
    $result = array_filter($data, fn($elem) => $elem["id"] == $args["id"]);

    if (count($result) === 0) {
        $params = [
            'status' => "404"
        ];
        return $this->get('renderer')->render($response->withStatus(404), 'user/show.phtml', $params);
    }

    $params = [
        'user' => $result,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'user/edit.phtml', $params);
    
})->setName("userIdEdit");



$app->delete('/user/{id}', function($request, $response, $args) use ($router) {
    $id = $args['id'];
    $data = json_decode(file_get_contents("../usersSaveFile.txt"), true);
    $deletedKey = null;
    foreach ($data as $key => $elem) {
        if ($elem['id'] == $id ) {
            $deletedKey = $key;
            break;
        }
    }
    if ($deletedKey !== null) {
        unset($data[$deletedKey]);
        $this->get('flash')->addMessage('success', 'User was deleted successfuly');

        file_put_contents("../usersSaveFile.txt", json_encode($data));
        $url = $router->urlFor("users");
        return $response->withRedirect($url);
    }

    file_put_contents("../usersSaveFile.txt", json_encode($data));
    $url = $router->urlFor("users");
    return $response->withRedirect($url);
});




$app->get('/main', function ($request, $response) {
    $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
    $params = [
        'cart' => $cart
    ];   
    return $this->get('renderer')->render($response, 'main/index.phtml', $params);
});

// BEGIN (write your solution here)
$app->post('/cart-items', function ($request, $response) {
    $item = $request->getParsedBodyParam('item');
    $cookie = json_decode($request->getCookieParam('cart', json_encode([])), true);


    if (!array_key_exists($item['name'], $cookie)) {
        $item['count'] = 1;
        $cookie[$item['name']] = $item;
    } else {
        $cookie[$item['name']]['count'] += 1;
    }

      // Кодирование корзины
    $encodedCart = json_encode($cookie);

    // Установка новой корзины в куку
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/main');

});


$app->delete('/cart-items', function ($request, $response) {
    $encodedCart = json_encode([]);
    return $response->withHeader('Set-Cookie', "cart={$encodedCart}")
        ->withRedirect('/main');
});


$app->run();

