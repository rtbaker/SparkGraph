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

$app->get('/admin/listgraphs.json', function(Silex\Application $app, Request $request){
    $sql = "select * from graph";
		$graphs = $app['db']->fetchAll($sql);
		
    return new JsonResponse($graphs, 200, array('Content-Type', 'application/json') );
})->bind('/admin/listgraphs.json');

$app->get('/admin/coredetail/{id}', function(Silex\Application $app, Request $request, $id){
    $statement = $app['db']->executeQuery("select * from sparkcore where id = ?", array($id));
		$core = $statement->fetch();

		$vars = $app['db']->fetchAll('select * from sparkvariable where sparkid = ?', array($id)); 

		$url = 'https://api.spark.io/v1/devices/' . $core['id'] . '?access_token=' . $core['token'];
		$app['monolog']->addDebug($url);
		$ch = curl_init($url);
		
		$options = array(
			CURLOPT_RETURNTRANSFER => true
		);
		
		curl_setopt_array( $ch, $options );
		$res = curl_exec($ch);
		
		// We don't want this to get out
		$core['token'] = "xxxxxxxxxxxxxxxxxxxx";
  
		$result = array();
		$result['core'] = $core;
		$result['vars'] = $vars;
		$result['cloud'] = json_decode($res);
		
  	return new JsonResponse($result, 200, array('Content-Type', 'application/json') );
})->bind('/admin/coredetail');


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

$app->post('/admin/checkVar', function (Silex\Application $app, Request $request) {
	try {
		$id = $request->get('id');
		$name = $request->get('name');
		$type = $request->get('type');
		$frequency = $request->get('frequency');
		
		$checked = $request->get('checked');
		$checkedB = ($checked == 'true') ? true : false;
		
		$app['monolog']->addDebug("checkVar -> id: " . $id . ", name: " . $name . ", type: " . $type .
			", checked: " . $checked . ", frequency: " . $frequency);
		
		$sql = "select * from sparkvariable where sparkid = ? and name = ?";
		$stmt = $app['db']->executeQuery($sql, array($id, $name));
		
		if ($row = $stmt->fetch()){
			// Update
			$app['db']->executeUpdate('UPDATE sparkvariable SET type = ?, collect = ?, frequency = ? WHERE sparkid = ? AND name = ?',
				array($type, $checkedB, $frequency, $id, $name),
				array(\PDO::PARAM_STR, \PDO::PARAM_BOOL, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR)
				);
		} else {
			// create
			$app['db']->executeUpdate('INSERT INTO sparkvariable VALUES (?, ?, ?, ?, ?)',
				array($id, $name, $type, $frequency, $checkedB),
				array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_BOOL)
				);
		}
	}
	catch (\Exception $e){
		$err = $e->getMessage();
		
		return new Response($err, 500);
	}
	
	return new Response("Record setup", 201);
	
})->method('POST')->bind('/admin/checkVar');

$app->post('/admin/addgraph', function (Silex\Application $app, Request $request) {
	try {
		$title = $request->get('title');
		$public = $request->get('public');
		
		$publicB = ($public == 'true') ? true : false;
		
		$app['monolog']->addDebug('Adding new graph to the database.');
		$app['monolog']->addDebug("title: " . $title . ", public: " . $public);
		
		$app['db']->executeUpdate('INSERT INTO graph (title, public) VALUES (?, ?)',
			array($title, $publicB),
			array(\PDO::PARAM_STR, \PDO::PARAM_BOOL));
	}
	catch (\Exception $e){
		$err = $e->getMessage();
		
		if (substr_count($e->getMessage(), "Duplicate entry")){
			$err = "Graph already exists in the database.";
		}
		return new Response($err, 500);
	}
	
	return new Response("Record added", 201);
	
})->method('POST')->bind('/admin/addgraph');

/* The end ! */
return $app;