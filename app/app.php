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
		    'monolog.level' => $app['debug'] ? Monolog\Logger::INFO : Monolog\Logger::ERROR,
));

$app->error(function (\Exception $e, $code) {
    return new Response('Error: ' + $e->getMessage());
});

/**
 * Home page
 */
$app->get('/', function (Silex\Application $app, Request $request) {
    return $app['twig']->render('index.twig');
})->bind('home');

$app->get('/listgraphs.json', function(Silex\Application $app, Request $request){
    $sql = "SELECT * FROM graph WHERE public = 1";
		$graphs = $app['db']->fetchAll($sql);
		
    return new JsonResponse($graphs, 200, array('Content-Type', 'application/json') );
})->bind('/listgraphs.json');

$app->get('/graphdata.json', function(Silex\Application $app, Request $request){
	$id = $request->get('id');
	if (!isset($id) || !is_numeric($id)){
		return new Response("No id specified", 500);
	}
	
  $sql = "SELECT * FROM graph WHERE public = 1 AND id = ?";
	$graph = $app['db']->fetchAll($sql, array($id));
	
	if (!count($graph)){
		return new Response("No such graph", 500);
	}	
		
 	$sql = "SELECT * FROM graphvariable WHERE graphid = ?";
	$vars = $app['db']->fetchAll($sql, array($id));
	
	$results = array();

	$results['cols'] = array();
	$results['rows'] = array();
	
	$datecol = array();
	$datecol['id'] = 'date';
	$datecol['label'] = 'Date';
	$datecol['type'] = 'datetime';
	
	$results['cols'][] = $datecol;
	
	$data = array();
	
	$colcount = 0;
	
	foreach ($vars as $var){
		$col = array();
		$col['id'] = $var['varname'];
		$col['label'] = $var['varname'];
		$col['type'] = 'number';
		$colcount++;
		
		$results['cols'][] = $col;
		$sql = "SELECT date, value FROM data WHERE sparkid = ? AND varname = ?";
		$sqldata = $app['db']->fetchAll($sql, array($var['sparkid'], $var['varname']));

		foreach ($sqldata as $row){
			if (!isset($data[$row['date']])){ $data[$row['date']] = array(); }
			
			if ($row['value'] != -500) { $data[$row['date']][] = $row['value']; }
		}
	}

	foreach (array_keys($data) as $d){
		$dO = new DateTime($d);
		$entry = array();
		$entry[] = array( 'v' => 'Date(' . 	$dO->format('Y') . ','.
																						($dO->format('n')-1) . ','.
																						$dO->format('j') . ','.
																						$dO->format('G') . ','.
																						$dO->format('i') . ','.
																						$dO->format('s') .
		 	')' );
		
	//	$entry[] = array( 'v' => $dO->format('Y/m/d H:i') );
		
		// Blank values as placeholders
		for ($i = 0; $i < $colcount; $i++){
			$entry[$i+1] = array();
		}
		
		$count = 1;
		foreach ($data[$d] as $v){
			$entry[$count] = array( 'v' => $v);
			$count++;
		}
		
		$results['rows'][] = array( 'c' => $entry);
	}
	
// return new JsonResponse($results, 200, array('Content-Type', 'application/json') );
return new Response(json_encode($results, JSON_NUMERIC_CHECK), 200);
})->bind('/graphdata.json');


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

$app->post('/admin/updatecoretoken', function (Silex\Application $app, Request $request) {
	try {
		$id = $request->get('id');
		$token = $request->get('token');
		
		$app['monolog']->addDebug('Updating core token.');
		$app['monolog']->addDebug("id: " . $id . ", token: " . $token);
		
		$app['db']->executeUpdate('UPDATE sparkcore SET token = ? WHERE id = ?', array($token, $id));
	}
	catch (\Exception $e){
		$err = $e->getMessage();
		return new Response($err, 500);
	}
	
	return new Response("Record updated", 201);
	
})->method('POST')->bind('/admin/updatecoretoken');

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

$app->get('/admin/graphdetail/{id}', function(Silex\Application $app, Request $request, $id){
    $statement = $app['db']->executeQuery("select * from graph where id = ?", array($id));
		
		if (!$graph = $statement->fetch()){
			return new Response("No such graph", 500);
		};

		$vars = $app['db']->fetchAll("select * from graphvariable where graphid = ?", array($id));
		
		$sql = 'SELECT sparkvariable.name AS variablename, sparkvariable.type, sparkvariable.frequency, ' .
			'sparkvariable.collect, sparkcore.id AS coreid, sparkcore.name AS corename' . 
			' FROM sparkvariable, sparkcore WHERE sparkvariable.sparkid = sparkcore.id AND sparkvariable.type IN (\'double\', \'int\')'; 

		$app['monolog']->addDebug($sql);
		$availablevars = $app['db']->fetchAll($sql);
		
		$result = array();
		$result['graph'] = $graph;
		$result['vars'] = $vars;
		$result['availablevars'] = $availablevars;
		
  	return new JsonResponse($result, 200, array('Content-Type', 'application/json') );
})->bind('/admin/graphdetail');

$app->post('/admin/graphvar', function (Silex\Application $app, Request $request) {
	try {
		$graphid = $request->get('graphid');
		$sparkid = $request->get('sparkid');
		$varname = $request->get('varname');
		$checked = $request->get('checked');
		
		$sql = "SELECT * FROM graphvariable WHERE graphid = ? AND sparkid = ? AND varname = ?";
		
		$vars = array($graphid, $sparkid, $varname);
		$stmt = $app['db']->executeQuery($sql, $vars);
		
		if ($row = $stmt->fetch()){
			// Exists already
			if ($checked == 'false'){
				// remove
				$app['db']->executeUpdate('DELETE FROM graphvariable WHERE graphid = ? AND sparkid = ? AND varname = ?',
					$vars);
			}
		} else {
			// Doesn't exist
			if ($checked == 'true'){
				// Add
				$app['db']->executeUpdate('INSERT INTO graphvariable VALUES (?, ?, ?)',
					$vars);
			}
		}	
	}
	catch (\Exception $e){
		$err = $e->getMessage();
		
		return new Response($err, 500);
	}
	
	return new Response("All good", 201);
	
})->method('POST')->bind('/admin/graphvar');

/* The end ! */
return $app;