<?php
namespace HummingJay;

class Server{
	public $live = false;

	// Response
	public $useHypermedia = false;
	public $title = '';
	public $description = '';
	public $httpStatus = 'HTTP/1.0 200 OK';
	public $links = [];
	public $responseData = []; // is cast to object and converted to JSON string
	public $headers = [];

	// Request
	public $uri = "";
	public $params = [];
	public $method = "OPTIONS";
	public $all_routes = [];
	public $data = null;
	public $rawData = "";
	public $dataWasEmpty = true;
	public $jsonError = JSON_ERROR_NONE;
	public $jsonMessage = "";


	public function __construct($live = false){
		if($live){
			$this->live = $live;
			$this->method = $_SERVER['REQUEST_METHOD'];
			$this->uri = $this->extractApiUri($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
			$this->rawData = file_get_contents("php://input");
			$this->decodeJson();
		}
	}



	// ==============================================================================================
	// Response

	public function send400($msg){ $this->httpStatus = 'HTTP/1.0 400 Bad Request'; $this->addData(["message"=>$msg]); }
	public function send404($msg){ $this->httpStatus = 'HTTP/1.0 404 Not Found'; $this->addData(["message"=>$msg]); }
	public function send405($msg){ $this->httpStatus = 'HTTP/1.0 405 Method Not Allowed'; $this->addData(["message"=>$msg]); }
	public function send500($msg){ $this->httpStatus = 'HTTP/1.0 500 Internal Server Error'; $this->addData(["message"=>$msg]); }

	public function send(){
		if($this->live){
			foreach($this->headers as $h){
				header($h);
			}
			header($this->httpStatus);
			header("Content-Type: application/json");
			header('Content-Type: text/html; charset=utf-8');
		}

		echo($this->getData());
	}

	public function header($str){
		array_push($this->headers, $str);
	}

	public function addData($data){
		$this->responseData = array_merge($this->responseData, $data);
	}


	public function getData(){
		$myResponseData = $this->responseData; // in PHP, this COPIES responseData!
		if($this->useHypermedia){
			$myResponseData["hypermedia"] = $this->getHypermedia();
		}
		return json_encode((object)$myResponseData);
	}

	public function hyperTitle($title){
		$this->title = $title;
		$this->useHypermedia = true;
	}

	public function hyperDescription($description){
		$this->description = $description;
		$this->useHypermedia = true;
	}

	public function hyperLink($link){
		if(!isset($link["method"])){ $link["method"] = "OPTIONS"; }
		array_push($this->links, $link);
		$this->useHypermedia = true;
	}


	public function getHypermedia(){
		$hypermedia = array();
		if($this->title != ""){ $hypermedia["title"] = $this->title; }
		if($this->description != ""){ $hypermedia["description"] = $this->description; }
		if(count($this->links) > 0){
			$hypermedia["links"] = array();
			foreach($this->links as $link){
				array_push($hypermedia["links"], $link);
			}
		}
		return (object)$hypermedia;
	}



	// ==============================================================================================
	// Request


	public function decodeJson(){
		if(strlen($this->rawData) == 0){ return; }
		$this->dataWasEmpty = false;
		$decoded_data = json_decode($this->rawData);
		$this->jsonError = json_last_error();

		switch ($this->jsonError) {
			case JSON_ERROR_NONE: $this->data = $decoded_data; break;
			case JSON_ERROR_DEPTH: $this->jsonMessage = 'Maximum stack depth exceeded'; break;
			case JSON_ERROR_STATE_MISMATCH: $this->jsonMessage = 'Underflow or the modes mismatch'; break;
			case JSON_ERROR_CTRL_CHAR: $this->jsonMessage = 'Unexpected control character found'; break;
			case JSON_ERROR_SYNTAX: $this->jsonMessage = 'Syntax error, malformed JSON'; break;
			case JSON_ERROR_UTF8: $this->jsonMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded'; break;
			default: $this->jsonMessage = 'Unknown error'; break;
		}
	}

	public function extractApiUri($req_uri, $api_base){
		$base_dir = dirname($api_base);
		$uri = preg_replace("'^($api_base|$base_dir)'", "", $req_uri); // '=regex delimiter
		return $uri === '' ? '/' : $uri;
	}


}

