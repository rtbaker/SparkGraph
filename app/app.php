<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


$app = require __DIR__.'/bootstrap.php';
$app['debug'] = true;

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider());

/**
 * Home page
 */
$app->get('/', function (Silex\Application $app, Request $request) {
    return $app['twig']->render('index.twig');
})
->bind('home');

$app->get('/admin', function (Silex\Application $app, Request $request) {
    return $app['twig']->render('admin.twig');
})
->bind('admin');

$app->get('/login', function(Silex\Application $app, Request $request) {
    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
})->bind('login');



/* The end ! */
return $app;