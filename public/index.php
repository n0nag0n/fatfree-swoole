<?php

// Autoload class
function modification($filename) {
	// if (defined('DIR_CATALOG')) {
	// 	$file = DIR_MODIFICATION . 'app/' .  substr($filename, strlen(DIR_APPLICATION));
	// } elseif (defined('DIR_OPENCART')) {
	// 	$file = DIR_MODIFICATION . 'install/' .  substr($filename, strlen(DIR_APPLICATION));
	// } else {
	// 	$file = DIR_MODIFICATION . 'public/' . substr($filename, strlen(DIR_APPLICATION));
	// }
	defined('DIR_MODIFICATION') or define('DIR_MODIFICATION', '../system/modification/');
	if (substr($filename, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
		$file = DIR_MODIFICATION . 'system/' . substr($filename, strlen(DIR_SYSTEM));
	}
	if (is_file($file)) {
		return $file;
	}
	return $filename;
}

function library($class) {
	defined('DIR_SYSTEM') or define('DIR_SYSTEM', '../system/');
	$file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';
	if (is_file($file)) {
		include_once(modification($file));
		return true;
	} else {
		return false;
	}
}

spl_autoload_register('library');
// spl_autoload_extensions('.php');

call_user_func(function ($f3) {

	/**
	 * Initialize the framework and configure the WebSocket server.
	 */
	$f3 = Base::instance();

	$f3->AUTOLOAD .= ';../;'.DIR_SYSTEM.'common/';
	$f3->DEBUG = 2;
	$f3->HALT = false;

	// Overwrite Fat-Free Framework's default error reporting level.
	error_reporting(
	    (E_ALL | E_STRICT) & ~(E_NOTICE | E_USER_NOTICE | E_WARNING | E_USER_WARNING)
	);

	$f3->route('GET /', function($f3) {
	    // echo json_encode([ 'this' => 'is', 'index' => 'co,', 'hello' => 'world' ])."\n"; 
	    echo '<h1>Index</h1>'."\n".'homepage'."\n";
	    var_dump($_GET);
	    // print_r($f3->hive());
	});
	$f3->route('GET /hey', function($fw) { echo json_encode([ 'some' => 'array', 'here' => 'would', 'be' => 'cool' ])."\n"; });
	$f3->route('GET /hive',function($f3){
		$f3->reroute('/path?arg=value#anchor');
	});
	$f3->route('GET /path',function(){

	    echo '<h1>Index</h1>'."\n";
	    var_dump($_GET);
		// if (Cache::instance()->exists('CRSF',$val)) {
		// 	echo "CRSF";
		// 	echo  '<pre>';
		// 	print_r($val);
		// 	echo '</pre>';
		// 	exit;
		// }
		echo  '<pre>';
		print_r(\Base::instance()->hive());
		echo '</pre>';
	});
	$f3->route('GET /hey', function($f3) {
	    echo json_encode([ 'some' => 'array', 'here' => 'would', 'be' => 'cool' ])."\n"; 
	});
	// print_r($f3->get('AUTOLOAD'));
	// Guide::instance();
	$f3->route('GET /auth',function($f3){
		var_dump($f3->REQUEST);
		if (PHP_SAPI == 'cli') {
			// echo  '<pre>';
			// print_r(\Base::instance()->hive());
			// echo '</pre>';
		} 

	});
	if (PHP_SAPI == 'cli')
		$f3->QUIET = true;
	else {
		$f3->HIGHLIGHT = true;

		$f3->ONERROR = function (Base $f3) {
			echo $f3->get('ERROR.text') . "\n";

			foreach (explode("\n", trim($f3->get('ERROR.trace'))) as $line) {
				echo $line . "\n";
			}
		};
		$f3->run();
	}

}, Base::instance());
