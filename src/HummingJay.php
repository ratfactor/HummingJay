<?php 
namespace HummingJay;


class HummingJay{
	public function __construct($route_str=NULL){
		if($route_str === NULL){ return; }
		$routes = $this->parseRouteString($route_str);
		$this->route($routes);
	}

	public function parseRouteString($route_str){
		$routes = array();
		// strtok: see http://stackoverflow.com/a/14789147/695615
		$line = strtok($route_str, "\r\n");
		while ($line !== false) {
			if(!preg_match("/(\S+)\s-\s(\S+)/", $line, $matches)){
				throw new \UnexpectedValueException("Bad line in the routing string: '$line'");
			}
			$routes[$matches[1]] = $matches[2]; // uri = classname
			$line = strtok("\r\n");
		}
		return $routes;
	}


	public function route($routes){
		$req = new Request(true); 
		$req->all_routes = $routes;
		
		$matchedResource = $this->matchUri($routes, $req->uri);
		if ($matchedResource === null){
			Response::send404("There is no resource at '{$req->uri}'.");
		}
		$req->params = $matchedResource["params"];

		$resourceObj = $this->makeResource($matchedResource["classname"], $req);
		if ($resourceObj === null){
			Response::send500("Could not resolve resource at '{$req->uri}'.");
		}
		$resourceObj->callMethod($req);
	}

	public function matchUri($routes, $uri){
		foreach ($routes as $res_uri => $res_class) {
			$res_regex = "'^".preg_replace("'\{([A-Za-z0-9_]+)\}'", "(?P<\\1>[^/]+)", $res_uri)."(?:\?.*)?$'";
			
			if(preg_match_all($res_regex, $uri, $matches)){
				return ["classname" => $res_class, "params" => $this->makeParameterHash($matches)];
			}
		}
		return null;
	}

	public function makeResource($classname, $req){
		if(!class_exists($classname)){
			return null;
		}
		return new $classname($req);
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

