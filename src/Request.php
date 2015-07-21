<?php
namespace HummingJay;

class Request{
	public $uri = "";
	public $params = [];
	public $method = "";
	public $all_routes = [];
	public $data = null;
	public $rawData = "";
	public $dataWasEmpty = true;
	public $jsonError = JSON_ERROR_NONE;
	public $jsonMessage = "";


	public function __construct($data = null){
		$this->data = $data;
		if(is_null($data)){
			$this->method = $_SERVER['REQUEST_METHOD'];
			$this->uri = $this->extractApiUri($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
			$this->rawData = file_get_contents("php://input");
			$this->decodeJson();
		}
	}

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

	private function extractApiUri($req_uri, $api_base){
		$base_dir = dirname($api_base);
		return preg_replace("'^($api_base|$base_dir)'", "", $req_uri);
	}

}

