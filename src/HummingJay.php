<?php 
namespace HummingJay;


class HummingJay{
	public $sentResponse = null; // to examine for testing
	private $server = null;

	public function __construct($routes=null, $server = null){
		$this->server = $server === null ? new Server(true) : $server;
		if($routes !== null){
			$this->route($routes);
		}
	}

	public function route($routes){
		$this->server->all_api_routes = $routes;
		
		$matchedResource = $this->matchUri($routes, $this->server->uri);
		if ($matchedResource === null){
			$this->server->hyperStatus(404, "Resource not found at '{$this->server->uri}'.");
			$this->sentResponse = $this->server->send();
			return;
		}
		$this->server->params = $matchedResource["params"];

		$resourceObj = $this->makeResource($matchedResource["classname"], $this->server);
		if ($resourceObj === null){
			$this->server->hyperStatus(500, "Resource at '{$this->server->uri}' has an internal error.");
			$this->sentResponse = $this->server->send();
			return;
		}
		$this->server = $resourceObj->callMethod($this->server);
		if($this->server){ 
			$this->sentResponse = $this->server->send(); 
		}
	}

	public function matchUri($routes, $uri){
		foreach ($routes as $route_uri => $route_class) {
			$uri_regex = preg_replace("'\{([A-Za-z0-9_]+)\}'", "(?P<\\1>[^/]+)", $route_uri);
			$uri_regex = preg_replace("'\{([A-Za-z0-9_]+)--->\}'", "(?P<\\1>.+)", $uri_regex);
			$uri_regex = "'^".$uri_regex."(?:\?.*)?$'";
			
			if(preg_match_all($uri_regex, $uri, $matches)){
				return ["classname" => $route_class, "params" => $this->makeParameterHash($matches)];
			}
		}
		return null;
	}

	public function makeResource($classname){
		if(!class_exists($classname)){
			return null;
		}
		return new $classname($this->server);
	}

	private function makeParameterHash($matches){
		$parameters = array();
		foreach($matches as $param => $vals){
			if(preg_match("/[A-Za-z_]/", $param)){
				$parameters[$param] = $vals[0]; // only one val per param
			}
		}
		return $parameters;
	}	



	
}

