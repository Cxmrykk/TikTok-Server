<?php
require 'vendor/autoload.php';

use TikScraper\Constants\UserAgents;
use TikScraper\Api;

$app = \Slim\Factory\AppFactory::create();

// Add Error Handling Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Define custom error handler for HttpNotFoundException
$errorMiddleware->setErrorHandler(
    Slim\Exception\HttpNotFoundException::class,
    function (Psr\Http\Message\ServerRequestInterface $request) {
        $response = new Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Page not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
);

// Instantiate TikScraper API
$api = new \TikScraper\Api([
    'use_test_endpoints' => false,
    'user_agent' => UserAgents::DEFAULT,
    'signer' => [
        'method' => 'remote',
        'url' => 'http://localhost:5001/signature',
        'close_when_done' => false
    ]
]);

// Define routes
$app->get('/user/{username}', function ($request, $response, $args) use ($api) {
    $username = $args['username'];
    $cursor = $request->getQueryParam('cursor', 0);
    $user = $api->user($username);
    $feed = $user->feed($cursor);
    if ($user->ok()) {
        $response->getBody()->write(json_encode($feed->getFeed()));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'Response was not OK', 'details' => $user->error()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/hashtag/{tag}', function ($request, $response, $args) use ($api) {
    $tag = $args['tag'];
    $cursor = $request->getQueryParam('cursor', 0);
    $hashtag = $api->hashtag($tag);
    $feed = $hashtag->feed($cursor);

    if ($hashtag->ok()) {
        $response->getBody()->write(json_encode($feed->getFeed()));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'Response was not OK', 'details' => $hashtag->error()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/music/{name}', function ($request, $response, $args) use ($api) {
    $name = $args['name'];
    $cursor = $request->getQueryParam('cursor', 0);
    $music = $api->music($name);
    $feed = $music->feed($cursor);
    if ($music->ok()) {
        $response->getBody()->write(json_encode($feed->getFeed()));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'Response was not OK', 'details' => $music->error()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/video/{id}', function ($request, $response, $args) use ($api) {
    $id = $args['id'];
    $video = $api->video($id);
    $feed = $video->feed();
    if ($video->ok()) {
        $response->getBody()->write(json_encode($feed->getFeed()));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'Response was not OK', 'details' => $video->error()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/trending', function ($request, $response) use ($api) {
    $cursor = $request->getQueryParam('cursor', '');
    $trending = $api->trending();
    $feed = $trending->feed($cursor);
    if ($trending->ok()) {
        $response->getBody()->write(json_encode($feed->getFeed()));
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write(json_encode(['error' => 'Response was not OK', 'details' => $trending->error()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
?>
