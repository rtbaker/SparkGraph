<?php

$app = require __DIR__.'/../app/bootstrap.php';

use Doctrine\DBAL\Schema\Table;

$schema = $app['db']->getSchemaManager();

if (!$schema->tablesExist('users')) {
    $users = new Table('users');
    $users->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $users->setPrimaryKey(array('id'));
    $users->addColumn('username', 'string', array('length' => 32));
    $users->addUniqueIndex(array('username'));
    $users->addColumn('password', 'string', array('length' => 255));
    $users->addColumn('roles', 'string', array('length' => 255));

    $schema->createTable($users);

/*
    $app['db']->insert('users', array(
      'username' => 'fabien',
      'password' => '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==',
      'roles' => 'ROLE_USER'
    ));
*/

    $app['db']->insert('users', array(
      'username' => 'admin',
      'password' => '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==',
      'roles' => 'ROLE_ADMIN'
    ));
}

if (!$schema->tablesExist('sparkcore')){
	$sparkcore = new Table('sparkcore');
	$sparkcore->addColumn('id', 'string', array('length' => 32));
	$sparkcore->addColumn('name', 'string', array('length' => 32));
	$sparkcore->addColumn('token', 'string', array('length' => 64));
	
	$sparkcore->setPrimaryKey(array("id"));
	
	$schema->createTable($sparkcore);
}

if (!$schema->tablesExist('sparkvariable')){
	$sparkvars = new Table('sparkvariable');
	$sparkvars->addColumn('sparkid', 'string', array('length' => 32));
	$sparkvars->addColumn('name', 'string', array('length' => 32));
	$sparkvars->addUniqueIndex(array('sparkid', 'name'));
	$sparkvars->addColumn('type', 'string', array('length' => 32));
	$sparkvars->addColumn('frequency', 'integer', array('default' => 10, "unsigned" => true));
	$sparkvars->addColumn('collect', 'boolean');

	$schema->createTable($sparkvars);
}

if (!$schema->tablesExist('data')){
	$data = new Table('data');
	$data->addColumn('sparkid', 'string', array('length' => 32));
	$data->addColumn('varname', 'string', array('length' => 32));
	$data->addColumn('value', 'text');
	$data->addColumn('date', 'datetime');
	
	$schema->createTable($data);
}

if (!$schema->tablesExist('graph')) {
	$graph = new Table('graph');
 	$graph->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
	$graph->setPrimaryKey(array('id'));
	$graph->addColumn('title', 'string', array('length' => 64));
	$graph->addColumn('public', 'boolean', array('default' => 0));
		
	$schema->createTable($graph);
}

if (!$schema->tablesExist('graphvariable')){
	$graphvar = new Table('graphvariable');
	$graphvar->addColumn('graphid', 'integer', array('unsigned' => true));
	$graphvar->addColumn('sparkid', 'string', array('length' => 32));
	$graphvar->addColumn('varname', 'string', array('length' => 32));
	
	$schema->createTable($graphvar);
}