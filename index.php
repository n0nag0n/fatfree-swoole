<?php

$path = __DIR__.'/vendor/autoload.php';
require $path;

/*
Swoole\Http\Request Object
(
    [fd] => 1
    [streamId] => 0
    [header] => Array
        (
            [host] => localhost:8081
            [connection] => keep-alive
            [upgrade-insecure-requests] => 1
            [user-agent] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36
            [accept] => text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9
            [sec-fetch-site] => none
            [sec-fetch-mode] => navigate
            [sec-fetch-user] => ?1
            [sec-fetch-dest] => document
            [accept-encoding] => gzip, deflate, br
            [accept-language] => en-US,en;q=0.9
        )

    [server] => Array
        (
            [query_string] => hi=there
            [request_method] => GET
            [request_uri] => /
            [path_info] => /
            [request_time] => 1590549302
            [request_time_float] => 1590549302.0154
            [server_protocol] => HTTP/1.1
            [server_port] => 8081
            [remote_port] => 46794
            [remote_addr] => 127.0.0.1
            [master_time] => 1590549302
        )

    [cookie] => Array
        (
            [PHPSESSID] => 81m3eli7dpe79r2bbqe2jebag1
            [adminer_sid] => 73d4313218dae8fcd1c36c9e2cefb3e8
            [adminer_key] => c04e27d9c3aa0abd3ed58d414b796fe3
        )

    [get] => Array
        (
            [hi] => there
        )

    [files] => 
    [post] => 
    [tmpfiles] => 
)
Swoole\Http\Response Object
(
    [fd] => 1
    [socket] => 
    [header] => 
    [cookie] => 
    [trailer] => 
)
*/

$fw = Base::instance();
$fw->HALT = false;
$fw->DEBUG = 3;
$fw->QUIET = true;

$fw->route('GET /', function($fw) { echo 'homepage'."\n"; var_dump($_GET); });
$fw->route('GET /hey', function($fw) { echo json_encode([ 'some' => 'array', 'here' => 'would', 'be' => 'cool' ])."\n"; });
$FatFree_Swoole = new n0nag0n\FatFree_Swoole;

$http = new Swoole\HTTP\Server("127.0.0.1", 9501);

$http->on("start", function (Swoole\HTTP\Server $server) {
    echo sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL;
});

$http->on(
    "request",
    function (Swoole\HTTP\Request $swooleRequest, Swoole\HTTP\Response $swooleResponse) use ($fw, $FatFree_Swoole) {
	    	$fw->set('ONREROUTE',function($url,$permanent) use ($swooleResponse) { 
			$swooleResponse->redirect($url); 
		});
		$FatFree_Swoole->process($swooleRequest, $swooleResponse);
		$swooleResponse->end();
    }
);

$http->start();
