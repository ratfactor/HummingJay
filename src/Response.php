<?php
namespace HummingJay;


class Response{
	private $useHypermedia = false;
	private $title = '';
	private $description = '';

	private $httpStatus = '';
	private $links = array();
	private $responseData = array(); // is cast to object and converted to JSON string

	function __construct($status = 'HTTP/1.0 200 OK'){
		$this->httpStatus = $status;
	}

	public static function send400($msg){ header('HTTP/1.0 400 Bad Request'); die($msg); }
	public static function send404($msg){ header('HTTP/1.0 404 Not Found'); die($msg); }
	public static function send405($msg){ header('HTTP/1.0 405 Method Not Allowed'); die($msg); }
	public static function send500($msg){ header('HTTP/1.0 500 Internal Server Error'); die($msg); }

	public function send(){
		header($this->httpStatus);
		header("Content-Type: application/json");
		header('Content-Type: text/html; charset=utf-8');

		$myResponseData = $this->responseData; // in PHP, this COPIES responseData!
		if($this->useHypermedia){
			$myResponseData["hypermedia"] = $this->getHypermedia();
		}
		echo(json_encode((object)$myResponseData));
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


	private function getHypermedia(){
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

	public function addData($data){
		$this->responseData = array_merge($this->responseData, $data);
	}






















	public function getHttpStatus(){
		return $this->httpStatus;
	}


	public function findLinkByHref($href){ // testing utility
		foreach($this->links as $link){
			if($link->href == $href){ return $link; }
		}
		return false;
	}

	public function findLinkByMethod($method){ // testing utility
		foreach($this->links as $link){
			if($link->method == $method){ return $link; }
		}
		return false;
	}

	public function addChildLink($href, $title){
		$this->addLink(new HmjResponseLinkChild($href, $title));
	}

	public function addParentLink($href, $title){
		$this->addLink(new HmjResponseLinkParent($href, $title));
	}

	public function addSelfLink($href){
		$this->addLink(new HmjResponseLinkSelf($href));
	}

	public function bareResponse($message){
		$this->bareResponseMsg = $message;
	}


	public function getCompleteResponseData(){ // for testing
		return $this->addHypermediaToResponseData();
	}

	public function doNotIncludeLinks(){
		$this->includeLinks = false;
	}

}

