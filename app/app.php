<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

$app = require __DIR__.'/bootstrap.php';
$app['debug'] = true;

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
));

$app->error(function (\Exception $e, $code) {
    return new Response('Error: ' + $e->getMessage());
});

/**
 * Home page
 */
$app->get('/', function (Silex\Application $app, Request $request) {
    return $app['twig']->render('index.twig');
})
->bind('home');

// Admin Page

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

// Overview tab

$app->get('/admin/overview.json', function(Silex\Application $app, Request $request){
    $sql = "select * from sparkcore";
		$cores = $app['db']->fetchAll($sql);
		
		$sql = "select * from graph";
		$graphs = $app['db']->fetchAll($sql);

		$overview = array(
			'no_of_graphs' => count($graphs),
			'no_of_cores' => count($cores)
		);
		
    return new JsonResponse($overview, 200, array('Content-Type', 'application/json') );
})->bind('/admin/overview.json');

// Spark Core handling 

$app->get('/admin/listcores.json', function(Silex\Application $app, Request $request){
    $sql = "select * from sparkcore";
		$cores = $app['db']->fetchAll($sql);
		
    return new JsonResponse($cores, 200, array('Content-Type', 'application/json') );
})->bind('/admin/listcores.json');

$app->post('/admin/addcore', function (Silex\Application $app, Request $request) {
	try {
		$id = $request->get('id');
		$name = $request->get('name');
		$token = $request->get('token');
		
		$app['monolog']->addDebug('Adding new core to the database.');
		$app['monolog']->addDebug("id: " . $id . ", name: " . $name . ", token: " . $token);
		
		$app['db']->executeUpdate('INSERT INTO sparkcore VALUES (?, ?, ?)', array($id, $name, $token));
	}
	catch (\Exception $e){
		$err = $e->getMessage();
		
		if (substr_count($e->getMessage(), "Duplicate entry")){
			$err = "Spark Core already exists in the database.";
		}
		return new Response($err, 500);
	}
	
	return new Response("Record added", 201);
	
})->method('POST')->bind('/admin/addcore');

/* The end ! */
return $app;