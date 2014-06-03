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
	
	$schema->createTable($sparkcore);
}