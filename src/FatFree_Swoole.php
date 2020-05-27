<?php 

namespace n0nag0n;

class FatFree_Swoole extends \Base {

	public function process(\Base $fw, \Swoole\HTTP\Request $swooleRequest, \Swoole\HTTP\Response $swooleResponse) {

		$processed_fw = $this->convertToFatFreeRequest($swooleRequest, $fw);
		$this->convertToSwooleResponse($swooleResponse, $processed_fw);
		// $slimRequest = $this->requestTransformer->toSlim($swooleRequest);
        // $slimResponse = $this->app->process($slimRequest, new Http\Response());

        // return $this->responseMerger->mergeToSwoole($slimResponse, $swooleResponse);
	}

	protected function convertToFatFreeRequest(\Swoole\HTTP\Request $swooleRequest, \Base $fw) {
		$processed_fw = clone $fw;
		$processed_fw->hive = $fw->hive;

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

		$processed_fw->hive['HEADERS'] = &$headers;
		$processed_fw->hive['AGENT'] = $processed_fw->agent();
		$processed_fw->hive['AJAX'] = $processed_fw->ajax();
		$processed_fw->hive['BODY'] = $swooleRequest->rawContent();
		$processed_fw->hive['CLI'] = FALSE;
		$processed_fw->hive['FRAGMENT'] = isset($uri['fragment'])?$uri['fragment']:'';
		$processed_fw->hive['HOST'] = $_SERVER['SERVER_NAME'];
		$processed_fw->hive['PATH'] = $path;
		$processed_fw->hive['QUERY'] = isset($uri['query'])?$uri['query']:'';
		$processed_fw->hive['ROOT'] = $_SERVER['DOCUMENT_ROOT'];
		$processed_fw->hive['SCHEME'] = $scheme;
		$processed_fw->hive['SEED'] = $processed_fw->hash($_SERVER['SERVER_NAME'].$base);
		$processed_fw->hive['TIME'] = &$_SERVER['REQUEST_TIME_FLOAT'];
		$processed_fw->hive['URI'] = &$_SERVER['REQUEST_URI'];
		$processed_fw->hive['VERB'] = &$_SERVER['REQUEST_METHOD'];
		foreach (explode('|',\Base::GLOBALS) as $global) {
			$lowercase_global = strtolower($global);
			$processed_fw->hive[$global] = $global === 'SERVER' ? array_combine(array_map('strtoupper', array_keys($swooleRequest->{$lowercase_global})), $swooleRequest->{$lowercase_global}) : $swooleRequest->{$lowercase_global};
		}

		//print_r($processed_fw->hive);
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