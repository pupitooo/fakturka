<?php

$ips = [
	'127.0.0.1',
	'93.99.190.249', // Francouzská 69
];

// Uncomment this line if you must temporarily take down your site for maintenance.
//if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
//	require '.maintenance.php';
//}

$container = require __DIR__ . '/../app/bootstrap.php';

$container->getService('application')->run();
