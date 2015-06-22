<?php 
namespace HummingJay;


class HummingJay{
	public function __construct($route_str){
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

	public function route($routes, $test_uri = NULL, $test_method = NULL){
		$uri = $test_uri ?: $this->serverUri();
		$method = $test_method ?: $_SERVER['REQUEST_METHOD'];

		foreach ($routes as $res_uri => $res_class) {
			$res_regex = "'^".preg_replace("'\{([A-Za-z0-9_]+)\}'", "(?P<\\1>[^/]+)", $res_uri)."(?:\?.*)?$'";
			if(preg_match_all($res_regex, $uri, $uri_param_matches)){
				if(!class_exists($res_class)){
					Response::send500("Could not resolve resource at '$uri'.");
				}
				$req = (object)[
					"uri" => $uri,
					"params" => $this->makeParameterHash($uri_param_matches),
					"method" => $method,
					"resource_uris" => $routes,
					"payload"=> $this->getPayload()
				];
				$resource = new $res_class($req);
				$resource->callMethod($req);
				return;
			}
		}
		Response::send404("There is no resource at '$uri'.");
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

	private function serverUri(){
		$uri = $_SERVER['REQUEST_URI'];
		// remove the script name and/or dirname from the incoming URI
		// NOTE: using ' as the regexp delimiters because the path likely has lots of /s
		$scriptname = $_SERVER['SCRIPT_NAME'];
		$scriptdir = dirname($scriptname);
		return preg_replace("'^($scriptname|$scriptdir)'", "", $uri);
	}

	private function getPayload(){
		$pl = [
			"raw"=>file_get_contents("php://input"),
			"exists"=>false,
			"json_valid"=>false,
			"json_error"=>JSON_ERROR_NONE,
			"json_msg"=>'',
			"json"=>null
		];
		if(strlen($pl["raw"]) == 0){ return $pl; }

		// we've got *something*, try to decode as JSON
		$pl["exists"] = true;
		$pl["json"] = json_decode($pl["raw"]);
		$pl["json_error"] = json_last_error();
		switch ($pl["json_error"]) {
			case JSON_ERROR_NONE: $pl['json_valid'] = true; break;
			case JSON_ERROR_DEPTH: $pl['json_msg'] = 'Maximum stack depth exceeded'; break;
			case JSON_ERROR_STATE_MISMATCH: $pl['json_msg'] = 'Underflow or the modes mismatch'; break;
			case JSON_ERROR_CTRL_CHAR: $pl['json_msg'] = 'Unexpected control character found'; break;
			case JSON_ERROR_SYNTAX: $pl['json_msg'] = 'Syntax error, malformed JSON'; break;
			case JSON_ERROR_UTF8: $pl['json_msg'] = 'Malformed UTF-8 characters, possibly incorrectly encoded'; break;
			default: $pl['json_msg'] = 'Unknown error'; break;
		}
		return $pl;
	}
}

