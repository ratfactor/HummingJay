<?php
namespace HummingJay;

class Resource{
	protected $title = '';
	protected $description = '';
	protected $halted = false;

	function __construct($server){
		return;
	}

	public function callMethod($server){
		if($this->halted){ return $server; }
		$method = strtolower($server->method);
		if(!method_exists($this, $method)){
			$server->send405("The ".strtoupper($method)." method is not supported by this serverource. Try an OPTIONS serveruest for a list of supported methods.");
			return $server;
		}
		return $this->$method($server);
	}

	public function halt(){
		$this->halted = true;
	}

	protected function options($server){
		$server->hyperTitle($this->title);
		$server->hyperDescription($this->description);

		// add parent and children hyperlinks
		foreach($server->all_api_routes as $test_uri => $classname){
			// replace uri params with any literals supplied so far
			foreach($server->params as $param => $value){
				$test_uri = str_replace('{'.$param.'}', $value, $test_uri);
			}
			
			if($test_uri == $server->uri) continue; // self!
			// test for parent
			if($test_uri == str_replace('\\', '/', dirname($server->uri))){
				$server->hyperLink(["title" => $test_uri, "href" => $test_uri, "rel" => 'parent']);
			}
			// test for children
			if(preg_match("'^".rtrim($server->uri, '/')."/([^/]+)$'", $test_uri, $matches)){
				$server->hyperLink(["title" => $matches[1], "href" => $test_uri, "rel" => 'child']);
			}
		}

		// add method hyperlinks for self
		foreach(["OPTIONS", "GET", "PUT", "POST", "DELETE", "PATCH", "HEAD"] as $method){
			$m = strtolower($method);
			if(method_exists($this, $m)){ 
				$server->hyperLink(["title"=> "Self $method", "href" => $server->uri, "rel" => 'self', "method" => $method]);
			}
		}

		return $server;
	}

}