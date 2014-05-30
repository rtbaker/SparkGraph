<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Providers

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../templates',
    'twig.options' => array('cache' => __DIR__ . '/../cache'),
));


// Service definitions

$databaseConfig = require __DIR__.'/dbconfig.php';
$app->register(new Silex\Provider\DoctrineServiceProvider(), $databaseConfig);




return $app;