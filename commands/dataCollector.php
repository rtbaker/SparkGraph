<?php
/**
	* Data collector, to be riun as a cron job.
	* Best run once a minute (probably).
	*
	*/
$lockFile = sys_get_temp_dir() . "/sparkCollector.lock";
if (!file_exists($lockFile)){ touch($lockFile); }

// Get a lock or quit ! (we don;t want 2 versions of this script running at the same time)
$fp = fopen($lockFile, 'r+');

if(!flock($fp, LOCK_EX | LOCK_NB)) {
	print "Eeek data collector already running !\n";
	exit(-1);
}

/* OK, we are the only version of this script running, let's go */

$app = require __DIR__.'/../app/bootstrap.php';

// Find out which variables to collect
$sql = 'SELECT sparkvariable.name, sparkvariable.frequency, sparkcore.id, sparkcore.token ' . 
	' FROM sparkvariable, sparkcore WHERE (sparkvariable.sparkid = sparkcore.id) and sparkvariable.collect = 1';
$entries = $app['db']->fetchAll($sql); 

foreach($entries as $e) {
	// Find last data entry for this var
	$sql = "SELECT MAX(date) FROM data WHERE sparkid = ? AND varname = ?";
	$stmt = $app['db']->executeQuery($sql, array($e['id'], $e['name']));
	
	print "Var: " . $e['name'] . "\n";
	$result = $stmt->fetch();
	$getValue = false;
	
	if ($result["MAX(date)"]){
		print "\texisting record\n";
		$last = new DateTime($result["MAX(date)"]);
		$gap = secondsSince($last);
		
		if ($gap > ($e['frequency'] * 60)){ $getValue = true; }
	} else {
		print "\tno existing record\n";
		$getValue = true;
	}

	$url = 'https://api.spark.io/v1/devices/';
	
	$options = array(
		CURLOPT_RETURNTRANSFER => true
	);
	
	if ($getValue){
		print "getting value !\n";
		$fullUrl = $url . $e['id'] . '/' . $e['name'] . '?access_token=' . $e['token'];
		print $fullUrl . "\n";
		
		$ch = curl_init($fullUrl);
		curl_setopt_array( $ch, $options );
		$res = curl_exec($ch);
		
		$data = json_decode($res);
		$val = $data->result;
		
		$app['db']->executeUpdate('INSERT INTO data VALUES (?, ?, ?, ?)',
			array($e['id'], $e['name'], $val, new DateTime("now") ),
			array(\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR, 'datetime'));
	}
}


/* release the lock ! */
flock($fp, LOCK_UN); 
fclose($fp);

# ---------------------------------------------------------------------------------------------------

function secondsSince($date){
	$now = new DateTime("now");
	
	$since = $date->diff($now);
	
	$minutes = $since->days * 24 * 60;
	$minutes += $since->h * 60;
	$minutes += $since->i;
	
	return (($minutes * 60) + $since->s);
}