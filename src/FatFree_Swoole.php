<?php 

namespace n0nag0n;

class FatFree_Swoole extends \Prefab {

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


		// $processed_fw->hive['HEADERS'] = &$headers;
		$HEADERS = &$processed_fw->ref('HEADERS');
		$HEADERS = $headers;

		// $processed_fw->hive['AGENT'] = $processed_fw->agent();
		$AGENT = &$processed_fw->ref('AGENT');
		$AGENT = $processed_fw->agent();

		// $processed_fw->hive['AJAX'] = $processed_fw->ajax();
		$AJAX = &$processed_fw->ref('AJAX');
		$AJAX = $processed_fw->ajax();

		// $processed_fw->hive['BODY'] = $swooleRequest->rawContent();
		$BODY = &$processed_fw->ref('BODY');
		$BODY = $swooleRequest->rawContent();

		// $processed_fw->hive['CLI'] = FALSE;
		$CLI = &$processed_fw->ref('CLI');
		$CLI = FALSE;

		// $processed_fw->hive['FRAGMENT'] = isset($uri['fragment'])?$uri['fragment']:'';
		$FRAGMENT = &$processed_fw->ref('FRAGMENT');
		$FRAGMENT = isset($uri['fragment'])?$uri['fragment']:'';

		// $processed_fw->hive['HOST'] = $_SERVER['SERVER_NAME'];
		$HOST = &$processed_fw->ref('HOST');
		$HOST = $_SERVER['SERVER_NAME'];

		// $processed_fw->hive['PATH'] = $path;
		$PATH = &$processed_fw->ref('PATH');
		$PATH = $path;

		// $processed_fw->hive['QUERY'] = isset($uri['query'])?$uri['query']:'';
		$QUERY = &$processed_fw->ref('QUERY');
		$QUERY = isset($uri['query'])?$uri['query']:'';

		// $processed_fw->hive['ROOT'] = $_SERVER['DOCUMENT_ROOT'];
		$ROOT = &$processed_fw->ref('ROOT');
		$ROOT = $_SERVER['DOCUMENT_ROOT'];

		// $processed_fw->hive['SCHEME'] = $scheme;
		$SCHEME = &$processed_fw->ref('SCHEME');
		$SCHEME = $scheme;

		// $processed_fw->hive['SEED'] = $processed_fw->hash($_SERVER['SERVER_NAME'].$base);
		$SEED = &$processed_fw->ref('SEED');
		$SEED = $processed_fw->hash($_SERVER['SERVER_NAME'].$base);

		// $processed_fw->hive['TIME'] = &$_SERVER['REQUEST_TIME_FLOAT'];
		$TIME = &$processed_fw->ref('TIME');
		$TIME = $_SERVER['REQUEST_TIME_FLOAT'];

		// $processed_fw->hive['URI'] = &$_SERVER['REQUEST_URI'];
		$URI = &$processed_fw->ref('URI');
		$URI = $_SERVER['REQUEST_URI'];

		// $processed_fw->hive['VERB'] = &$_SERVER['REQUEST_METHOD'];
		$VERB = &$processed_fw->ref('VERB');
		$VERB = $_SERVER['REQUEST_METHOD'];

		foreach (explode('|',\Base::GLOBALS) as $global) {
			$lowercase_global = strtolower($global);
			$globalval = &$processed_fw->ref($global);

			$globalval = $global === 'SERVER' ? array_combine(array_map('strtoupper', array_keys($swooleRequest->{$lowercase_global})), $swooleRequest->{$lowercase_global}) : $swooleRequest->{$lowercase_global};
		}
		// $processed_fw->sync('REQUEST');
		// print_r($processed_fw);
		$processed_fw->run();
		return $processed_fw;
	}

	protected function convertToSwooleResponse(\Swoole\HTTP\Response $swooleResponse, \Base $processed_fw)  {
		if(!empty($processed_fw->RESPONSE)) {
			$swooleResponse->header('Content-Length', (string) strlen($processed_fw->RESPONSE));
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
