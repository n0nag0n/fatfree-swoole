<?php 

namespace App;

use \Swoole\HTTP\Server;
use \Swoole\HTTP\Request;
use \Swoole\HTTP\Response;

class swooleHttpServer extends \Prefab {

	public function __construct(Server $server) {
		$this->set($server);
		$this->register($server);
	}

	private function set(Server $server) {
		$server->set(array(
			'reactor_num'   => 16,	 // reactor thread num
			'worker_num'	=> 16,	 // worker process num
			'backlog'	   => 128,   // listen backlog
			'max_request'   => 50,
			'dispatch_mode' => 1,
		));
	}
	private function register(Server $server) {
		$server->on('start', [$this, 'onStart']);
		$server->on('receive', [$this, 'onReceive']);
		$server->on('task', [$this, 'onTask']);
		$server->on('finish', [$this, 'onFinish']);
		$server->on('shutdown', [$this, 'onShutdown']);
		$server->on('request', [$this, 'onRequest']);
	}

	private function debug(string $message) {
		$date = date('Y-m-d H:i:s');
		$memory = round(memory_get_usage(true) / 1000 / 1000, 3) . ' MB';
		fwrite(STDOUT, $date . ' | ' . $memory . ' | ' . $message . "\n");
	}

	public function onStart(Server $server) {
		$this->debug(sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL);
	}

	/**
	 * callback function is executed in the worker process
	 */
	public function onReceive($http, $fd, $from_id, $data) {

		// Deliver asynchronous tasks
		$task_id = $http->task($data);
		echo "Dispatch AsyncTask: id=$task_id\n";
	}

	/**
	 * Processing asynchronous tasks (this callback function executes in the task process)
	 */
	public function onTask($http, $task_id, $from_id, $data) {

		echo "New AsyncTask[id=$task_id]".PHP_EOL;
		// Return the result of task execution
		$http->finish("$data -> OK");
	}

	/**
	 * Results of processing asynchronous tasks (this callback function is executed in the worker process)
	 */
	public function onFinish($http, $task_id, $data) {
		echo "AsyncTask[$task_id] Finish: $data".PHP_EOL;
	}
	public function onShutdown(Server $server) {
		$this->debug('Swoole http server Shutting down');
	}

	public function onRequest(Request $swooleRequest, Response $swooleResponse) {
		// if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
		// 	$response->end();
		// 	return;
		// }

		\Base::instance()->set('ONREROUTE',function($url,$permanent) use ($swooleResponse) { 
			$swooleResponse->redirect($url); 
		});
		$this->process($swooleRequest, $swooleResponse);
		$swooleResponse->end();
	}

	public function process(\Swoole\HTTP\Request $swooleRequest, \Swoole\HTTP\Response $swooleResponse) {

		$processed_fw = $this->convertToFatFreeRequest($swooleRequest);
		$this->convertToSwooleResponse($swooleResponse, $processed_fw);
	}

	protected function convertToFatFreeRequest(\Swoole\HTTP\Request $swooleRequest) {
		$this->fw = \Base::instance();
		$processed_fw = clone $this->fw;
		// $processed_fw = $this->fw->recursive($this->fw, function($val){
		// 	return $val;
		// });
		// copy server vars
		foreach ($swooleRequest->server as $key=>$val) {
			$tmp=strtoupper($key);
			$_SERVER[$tmp] = $val;
		}

		// copy headers
		$headers = [];
		foreach ($swooleRequest->header as $key=>$val) {
			$tmp=strtoupper(strtr($key,'-','_'));
			// TODO: use ucwords delimiters for php 5.4.32+ & 5.5.16+
			$key=strtr(ucwords(strtolower(strtr($key,'-',' '))),' ','-');
			$headers[$key]=$val;
			if (isset($_SERVER['HTTP_'.$tmp]))
				$headers[$key]=&$_SERVER['HTTP_'.$tmp];
		}

		
		//print_r($swooleRequest->header);
		//print_r($_SERVER);

		$base=rtrim($processed_fw->fixslashes(
			dirname($_SERVER['SCRIPT_NAME'])),'/');
		$scheme=isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ||
			isset($headers['X-Forwarded-Proto']) &&
			$headers['X-Forwarded-Proto']=='https'?'https':'http';
		$uri=parse_url((preg_match('/^\w+:\/\//',$_SERVER['REQUEST_URI'])?'':
				$scheme.'://'.$_SERVER['SERVER_NAME']).$_SERVER['REQUEST_URI']);
		$path=preg_replace('/^'.preg_quote($base,'/').'/','',$uri['path']);


		$val = [
		'HEADERS' => &$headers,
		'AGENT' => $processed_fw->agent(),
		'AJAX' => $processed_fw->ajax(),
		'BODY' => $swooleRequest->rawContent(),
		'CLI' => FALSE,
		'FRAGMENT' => isset($uri['fragment'])?$uri['fragment']:'',
		'HOST' => $_SERVER['SERVER_NAME'],
		'PATH' => $path,
		'QUERY' => isset($uri['query'])?$uri['query']:'',
		'ROOT' => $_SERVER['DOCUMENT_ROOT'],
		'SCHEME' => $scheme,
		'SEED' => $processed_fw->hash($_SERVER['SERVER_NAME'].$base),
		'TIME' => &$_SERVER['REQUEST_TIME_FLOAT'],
		'URI' => &$_SERVER['REQUEST_URI'],
		'VERB' => &$_SERVER['REQUEST_METHOD']
		];
		foreach ($val as $hive => $value) {
			$$hive = &$processed_fw->ref($hive);
			$$hive = $value;
		}
		foreach (explode('|',\Base::GLOBALS) as $global) {
			$lowercase_global = strtolower($global);
			$globalval = &$processed_fw->ref($global);
			$globalval = $global === 'SERVER' ? array_combine(array_map('strtoupper', array_keys($swooleRequest->{$lowercase_global})), $swooleRequest->{$lowercase_global}) : $swooleRequest->{$lowercase_global};
		}
		// $processed_fw->sync('REQUEST');
		$processed_fw->run();
		return $processed_fw;
	}

	protected function convertToSwooleResponse(\Swoole\HTTP\Response $swooleResponse, \Base $processed_fw) {
		if(!empty($processed_fw->RESPONSE)) {
			$swooleResponse->header('Content-Length', (string) strlen($processed_fw->RESPONSE));
			$swooleResponse->header('Server', (string) $processed_fw->PACKAGE);
		}

		// deal with cookies
		// if($processed_fw->HEADERS['Set-Cookie']) {

		// }

		// need some work on capturing the status code
		$swooleResponse->status(isset($processed_fw->ERROR['code']) ? $processed_fw->ERROR['code'] : 200);

		if($processed_fw->RESPONSE) {
			$swooleResponse->write($processed_fw->RESPONSE);
		}
		
	}
}