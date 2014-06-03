<?php
use Symfony\Component\Security\Core\User\User;

$app = require __DIR__.'/../app/bootstrap.php';

$password = $argv[1];

$stmt = $app['db']->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower('admin')));

if (!$user = $stmt->fetch()) {
	throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
}

$usero = new User($user['username'], $user['password'], explode(',', $user['roles']), true, true, true, true);

if (!is_object($usero)){
	print "No admin user !\n";
	exit(1);
}

$encoder = $app['security.encoder_factory']->getEncoder($usero);
$pass = $encoder->encodePassword($password, $usero->getSalt());

print "Old pass: " . $usero->getPassword() . "\n";
print "New password: " . $pass . "\n";

$app['db']->update('users', array('password' => $pass), array('username' => 'admin'));