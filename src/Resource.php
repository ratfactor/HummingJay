<?php
namespace HummingJay;

class Resource{
	protected $title = '';
	protected $description = '';

	function __construct($req){
		return;
	}

	public function callMethod($req){
		$method = strtolower($req->method);
		if(!method_exists($this, $method)){
			Response::send405("The ".strtoupper($method)." method is not supported by this resource. Try an OPTIONS request for a list of supported methods.");
		}
		$res = $this->$method($req, new Response());
		if($res){ $res->send(); }
	}

	protected function options($req, $res){
		$res->hyperTitle($this->title);
		$res->hyperDescription($this->description);

		// add parent and children hyperlinks
		foreach($req->all_routes as $test_uri => $classname){
			// replace uri params with any literals supplied so far
			foreach($req->params as $param => $value){
				$test_uri = str_replace('{'.$param.'}', $value, $test_uri);
			}
			
			if($test_uri == $req->uri) continue; // self!
			// test for parent
			if($test_uri == str_replace('\\', '/', dirname($req->uri))){
				$res->hyperLink(["title" => $test_uri, "href" => $test_uri, "rel" => 'parent']);
			}
			// test for children
			if(preg_match("'^".rtrim($req->uri, '/')."/([^/]+)$'", $test_uri, $matches)){
				$res->hyperLink(["title" => $matches[1], "href" => $test_uri, "rel" => 'child']);
			}
		}

		// add method hyperlinks for self
		foreach(["OPTIONS", "GET", "PUT", "POST", "DELETE", "PATCH", "HEAD"] as $method){
			$m = strtolower($method);
			if(method_exists($this, $m)){ 
				$res->hyperLink(["title"=> "Self $method", "href" => $req->uri, "rel" => 'self', "method" => $method]);
			}
		}

		return $res;
	}

}