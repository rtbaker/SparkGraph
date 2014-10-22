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

$app->register(new Silex\Provider\SecurityServiceProvider());

$app['security.firewalls'] =  array(
		    'admin' => array(
		        'pattern' => '^/admin',
		        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
		        'users' => $app->share(function () use ($app) {
						    return new SparkGraph\UserProvider($app['db']); }),
						'logout' => array('logout_path' => '/admin/logout'),
		    ),
			'unsecured' => array(
				        'anonymous' => true,
	    ),
);


return $app;