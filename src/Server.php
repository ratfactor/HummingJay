<?php
namespace HummingJay;

class Server{
	public $live = false;
	public $all_api_routes = [];

	// Request
	public $uri = "";
	public $params = [];
	public $method = "OPTIONS";
	public $requestData = null;
	public $rawRequestData = "";
	public $jsonError = 'none';

	// Response
	public $useHypermedia = false;
	public $hyperTitle = '';
	public $hyperDescription = '';
	public $hyperLinks = [];
	public $httpStatus = 200;
	public $responseData = [];
	public $responseHeaders = [];
	public $httpStatuses = [
		100 => 'Continue',               408 => 'Request Timeout',
		101 => 'Switching Protocols',    409 => 'Conflict',
		102 => 'Processing',             410 => 'Gone',
		200 => 'OK',                     411 => 'Length Required',
		201 => 'Created',                412 => 'Precondition Failed',
		202 => 'Accepted',               413 => 'Request Entity Too Large',
		203 => 'Non-Authoritative',      414 => 'Request-URI Too Long',
		204 => 'No Content',             415 => 'Unsupported Media Type',
		205 => 'Reset Content',          416 => 'Requested Range Not Satisfiable',
		206 => 'Partial Content',        417 => 'Expectation Failed',
		207 => 'Multi-Status',           418 => 'I am a teapot',                                               
		208 => 'Already Reported',       422 => 'Unprocessable Entity',                                        
		226 => 'IM Used',                423 => 'Locked',                                                      
		300 => 'Multiple Choices',       424 => 'Failed Dependency',                                           
		301 => 'Moved Permanently',      426 => 'Upgrade Required',                                            
		302 => 'Found',                  428 => 'Precondition Required',                                       
		303 => 'See Other',              429 => 'Too Many Requests',                                           
		304 => 'Not Modified',           431 => 'Request Header Fields Too Large',                             
		305 => 'Use Proxy',              500 => 'Internal Server Error',
		306 => 'Reserved',               501 => 'Not Implemented',
		307 => 'Temporary Redirect',     502 => 'Bad Gateway',
		308 => 'Permanent Redirect',     503 => 'Service Unavailable',
		400 => 'Bad Request',            504 => 'Gateway Timeout',
		401 => 'Unauthorized',           505 => 'HTTP Version Not Supported',
		402 => 'Payment Required',       506 => 'Variant Also Negotiates',                      
		403 => 'Forbidden',              507 => 'Insufficient Storage',                                        
		404 => 'Not Found',              508 => 'Loop Detected',                                               
		405 => 'Method Not Allowed',     510 => 'Not Extended',                                                
		406 => 'Not Acceptable',         511 => 'Network Auth Required',  
		407 => 'Proxy Auth Required'
	];	


	public function __construct($live = false){
		if($live){
			$this->live = true;
			$this->method = $_SERVER['REQUEST_METHOD'];
			$this->uri = $this->extractApiUri($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
			$this->rawRequestData = file_get_contents("php://input");
			$this->decodeJson();
		}
	}



	// ==============================================================================================
	// Request Methods


	public function decodeJson(){
		$decoded_data = json_decode($this->rawRequestData);
		switch (json_last_error()) {
			case JSON_ERROR_NONE: $this->requestData = $decoded_data; break;
			case JSON_ERROR_DEPTH: $this->jsonError = 'stack_depth_exceeded'; break;
			case JSON_ERROR_STATE_MISMATCH: $this->jsonError = 'state_mismatch'; break;
			case JSON_ERROR_CTRL_CHAR: $this->jsonError = 'unexpected_control_char'; break;
			case JSON_ERROR_SYNTAX: $this->jsonError = 'syntax_error'; break;
			case JSON_ERROR_UTF8: $this->jsonError = 'malformed_utf8'; break;
			default: $this->jsonError = 'unknown'; break;
		}
		if(strlen($this->rawRequestData) == 0){ $this->jsonError = 'empty'; }
		if($this->jsonError !== 'none'){
			if($this->method == 'PUT' || $this->method == 'POST' || $this->method == 'PATCH'){
				$this->hyperStatus(400, "There was an error parsing the expected JSON Data: {$this->jsonError}");
				$this->addResponseData(["json_error"=>$this->jsonError]);
			}
		}
	}

	public function extractApiUri($req_uri, $api_base){
		$base_dir = dirname($api_base);
		$uri = preg_replace("'^($api_base|$base_dir)'", "", $req_uri); // '=regex delimiter
		return $uri === '' ? '/' : $uri;
	}



	// ==============================================================================================
	// Response Methods


	public function setStatus($code, $description = null){
		// $description parameter has been deprecated, it is confusing
		// call hyperDescription() separately or use hyperStatus() instead
		$this->httpStatus = $code;
		$this->hyperDescription($description);
	}

	public function makeStatusHeader($code){
		$status = "HTTP/1.0 ".$this->makeStatusLine($code);
		return $status;
	}

	public function makeStatusLine($code){
		if(array_key_exists($code, $this->httpStatuses)){
			return "$code {$this->httpStatuses[$code]}";
		}
		return $code;
	}

	public function addHeader($str){
		array_push($this->responseHeaders, $str);
	}

	public function send(){
		$myHeaders = $this->responseHeaders; // copy array before modifying
		array_push($myHeaders, $this->makeStatusHeader($this->httpStatus));
		array_push($myHeaders, 'Content-Type: application/json');
		$body = json_encode($this->makeResponseBody());
		if($this->live){
			foreach($myHeaders as $h){ header($h); }
			echo($body);
		}
		return ['headers'=>$myHeaders,'body'=>$body]; // to examine in testing
	}


	public function addResponseData($data){
		$this->responseData = array_merge($this->responseData, $data);
	}

	public function addData($data){
		// this method is deprecated - confusing name
		$this->addResponseData($data);
	}


	public function makeResponseBody(){
		$myResponseData = $this->responseData; // copy array before modifying
		if($this->useHypermedia){
			$myResponseData["hypermedia"] = $this->getHypermedia();
		}
		return $myResponseData;
	}

	public function hyperTitle($title){
		$this->hyperTitle = $title;
		$this->useHypermedia = true;
	}

	public function hyperDescription($description){
		$this->hyperDescription = $description;
		$this->useHypermedia = true;
	}

	public function hyperLink($link){
		$default = [
			"method"=>"OPTIONS",
			"title"=>"(untitled link)",
			"href"=>$this->uri,
			"rel"=>"none"
		];
		$link = array_merge($default, $link);
		array_push($this->hyperLinks, $link);
		$this->useHypermedia = true;
	}

	public function hyperStatus($code, $description){
		$this->httpStatus = $code;
		$this->hyperTitle($this->makeStatusLine($code));
		$this->hyperDescription($description);
	}

	public function getHypermedia(){
		$hypermedia = array();
		if($this->hyperTitle != ""){ $hypermedia["title"] = $this->hyperTitle; }
		if($this->hyperDescription != ""){ $hypermedia["description"] = $this->hyperDescription; }
		if(count($this->hyperLinks) > 0){
			$hypermedia["links"] = array();
			foreach($this->hyperLinks as $link){
				array_push($hypermedia["links"], $link);
			}
		}
		return $hypermedia;
	}

}

