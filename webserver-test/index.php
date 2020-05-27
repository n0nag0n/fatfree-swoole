<?php

$path = __DIR__.'/../vendor/autoload.php';
require $path;

$fw = Base::instance();
$fw->HALT = false;
$fw->DEBUG = 3;

$fw->route('GET /', function($fw) { echo 'homepage'."\n"; var_dump($_GET); });
$fw->route('GET /hey', function($fw) { echo json_encode([ 'some' => 'array', 'here' => 'would', 'be' => 'cool' ])."\n"; });

$fw->run();