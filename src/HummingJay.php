<?php 
namespace HummingJay;


class HummingJay{
	public $routeStringError = 'none';
	public $sentResponse = null; // to examine for testing
	private $server = null;

	public function __construct($route_str=null, $server = null){
		$this->server = $server === null ? new Server(true) : $server;
		if($route_str !== null){
			$routes = $this->parseRouteString($route_str);
			$this->route($routes);
		}
	}

	public function parseRouteString($route_str){
		$routes = array();
		// strtok: see http://stackoverflow.com/a/14789147/695615
		$line = strtok($route_str, "\r\n");
		while ($line !== false) {
			if(!preg_match("/(\S+)\s-\s(\S+)/", $line, $matches)){
				$this->routeStringError = "Error: '$line' did not fit the format '/uri/path - ClassName'";
				return [];
			}
			$routes[$matches[1]] = $matches[2]; // uri = classname
			$line = strtok("\r\n");
		}
		return $routes;
	}


	public function route($routes){
		$this->server->all_api_routes = $routes;
		
		$matchedResource = $this->matchUri($routes, $this->server->uri);
		if ($matchedResource === null){
			$this->server->hyperTitle = 'Error 404 Not Found';
			$this->server->setStatus(404, "Resource not found at '{$this->server->uri}'.");
			$this->sentResponse = $this->server->send();
			return;
		}
		$this->server->params = $matchedResource["params"];

		$resourceObj = $this->makeResource($matchedResource["classname"], $this->server);
		if ($resourceObj === null){
			$this->server->setStatus(500, "Resource at '{$this->server->uri}' has an internal error.");
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
			$uri_regex = "'^".preg_replace("'\{([A-Za-z0-9_]+)\}'", "(?P<\\1>[^/]+)", $route_uri)."(?:\?.*)?$'";
			
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

