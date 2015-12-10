# HummingJay

## Overview

HummingJay is a PHP 5.5+ library for creating REST APIs using the **hm-json** (todo: hm-json needs a stand-alone spec page!) format to deliver hypermedia and JSON data to clients. It has methods for routing, hypermedia generation, JSON data i/o, and handling HTTP communication.

## Installation

	composer require ratfactor/HummingJay

## Tiny Example

	<?php
	require "vendor/autoload.php";

	class Foo extends HummingJay\Resource{}

	$api = new HummingJay\HummingJay("/foo - Foo");
	?>

This example is unrealistically tiny.  But it is perfectly valid and shows how HummingJay routes requests: URI paths `"/foo"` to resolve to resources `Foo`.

## Demo

Look in the source at the heavily commented `demo/index.php` file for a much more thorough example of creating resources.  Also, see "Running Tests/Demo" below.

## Routing

The constructor of the `HummingJay\HummingJay` class takes a string containing a list of routes.  The format is very simple: the routes are separated by newlines ("\n") and the URI of each route is separated from its associated Resource name by a hypen (`-`).

Here's an example using PHP's nowdoc string format:

	$api = new HummingJay\HummingJay(<<<'API'
	/ - MyRoot
	/foo - Foo
	/foo/bars - BarCollection
	/foo/bars/{bar_id} - SpecificBar
	API
	);

Here you can see that we've defined four possible URIs.  One of them, `/foo/bars/{bar_id}`, has a parameter, which will match URIs which fit the pattern (e.g. `/foo/bars/31` or `/foo/bars/Cheers`). 

The four URIs will be handled by an instance of the resource class specified. When you try to reach the URI `/foo`, control will be passed off to the resource defined by the `Foo` class. 

### Match-to-the-end params

There is a special syntax, `{foo--->}`, for creating a final parameter that matches everything to the end of the URI.

Example:

	$api = new HummingJay\HummingJay("/foo/{string--->} - HelloFoo");

This creates an API with a single route which will match URIs such as

	/foo/Hello-World
	/foo/etc/rc.d/chicken.txt

In these two cases, the value of the parameter named `string` would be equal to the strings 'Hello-World' and 'etc/rc.d/chicken.txt' respectively.

See 'Getting request data from $server' below to learn how to access URI parameters.

## Creating Resources

To create a new resource, extend the `HummingJay\Resource` class.  Example:

	class Foo extends HummingJay\Resource{
		$title = "The Foo Resource!";
		$description = "I don't do much.  Try a GET to get a list of stuff!";
	}

As shown, it is recommended that you also customize the title and description of your resource.  These will be viewable by applications (such as hm-json Browser) which understand the hm-json formatted hypermedia.

If the resource should not automatically attempt to decode JSON request data, you can add an optional setting:

	$decodeJson = false;

Add as many HTTP method handlers as you need (supported: OPTIONS, GET, PUT, POST, DELETE, PATCH, HEAD).  Here's a GET which will return a JSON-encoded object containing a parameter named "foo_id".

	class Foo extends HummingJay\Resource{
		public function get($server){
			$server->addResponseData(["foo_id"=>"1003"]);
			return $server;
		}
	}
	
Keep reading for more explanation about this handler example.

_Note: HummingJay provides a default OPTIONS method.  You can extend it to add functionality.  See the  `Book` and `ReviewsCollection` resource classes in demo/index.php for examples of this._

### The $server object

The `$server` parameter passed to every method handler is an instance of the \HummingJay\Server class.  It is the HummingJay interface for all request and response functionality (and is thus an abstraction of the web server).

### Getting request data from $server

When passed to a method handler, `$server` contains the following properties about the incoming HTTP request:

Property      | Description
--------------|---------------------------------
`uri`               | the URI of the requested resource
`params`            | an associative array of parameters from the URI
`method`            | the HTTP method used, e.g. POST
`requestData`       | deserialized JSON data (or null if none)
`rawRequestData`    | raw request body (string)
`jsonError`         | string with terse description of error or 'none'

Here's an example which gets a URI parameter and some JSON data from the request body:

	// route string: /foo/bar/{bar_id} - Foo
	// request: PUT /foo/bar/74
	// request body: {'bardata':[3,6,4,9]}
	
	class Foo extends \HummingJay\Resource{
		public function put($server){
			$mybar = $server->params['bar_id'];  // 74
			if($requestData !== null){
				$bars->update(
					$mybar, 
					$requestData->bardata
				);
			}
		}
	}


### Building a response with $server

When `$server` is returned from a method handler, it contains the instructions for the HTTP response headers and body you wish to send back to the client.

`$server` has the following methods for modifying the outgoing HTTP response:

Method        | Description
--------------|---------------------------------
`setStatus($num)`          | set the HTTP status code
`addHeader($str)`          | add custom HTTP header
`addResponseData($data)`   | add any PHP data (will be JSON-encoded)
`hyperTitle($str)`         | set the hypermedia title
`hyperDescription($str)`   | set the hypermedia description
`hyperLink($data)`         | add a hypermedia link
`hyperStatus($num, $str)`  | set HTTP status code with hypermedia

#### $server->setStatus($num)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->setStatus(500);
			return $server;
		}
	}

See `src/Server.php` for a complete list of the HTTP status codes that HummingJay understands. You are free to use a code not in the list, but it will not have a text description (which is HTTP legal).

See hyperStatus() for a more friendly way to inform humans and computers of the response status.

#### $server->addHeader($str)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->addHeader("X-Custom-Message: Hello World!");
			return $server;
		}
	}

This can be anything you like.  Note that HummingJay already automatically sets the Content-Type for JSON for you.

#### $server->addResponseData($data)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->addResponseData(['foo'=>'bar']);
			return $server;
		}
	}

In the above example, the associative array added to the response will be converted into the following response body and sent back to the client:

	{ "foo": "bar" }

HummingJay relies on PHP's build-in json_encode() function.  It has reasonable rules for handling sequential arrays, associative arrays, objects, etc.

Successive calls to `addResponseData()` merges the data using PHP's built-in `array_merge()` function.

	$server->addResponseData(["dog"=>"Sparky"]);
	$server->addResponseData(["cat"=>"Fuzzy"]);
	return $server;

Results in response body:

	{ "dog": "Sparky", "cat": "Fuzzy" }


#### $server->hyperTitle($str) and hyperDescription($str)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->hyperTitle("The Foo Resource");
			$server->hyperDescription("I contain all of the FOO!");
			return $server;
		}
	}

As soon as you add any of the hypermedia properties such as title, description, or hyperlink, the $server object knows to return hm-json hypermedia.  The above example will produce the following response body:

	{
		"hypermedia":{
			"title": "The Foo Resource",
			"description": "I contain all of the FOO!"
		}
	}


#### $server->hyperLink($data)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->hyperLink([
				"method"=>"GET",
				"title"=>"Woggle",
				"href"=>'/woggles/woggle',
				"rel"=>"item"
			]);
			return $server;
		}
	}

This example will add the link to the links array in the hypermedia property of the JSON response like so:

	{
		"hypermedia": {
			"links": [
				{
					"method": "OPTIONS",
					"title": "books",
					"href": "/books",
					"rel": "child"
				}
			]
		}
	}

It is important to understand that the default OPTIONS method provided by HummingJay generates linked hm-json hypermedia for your API.  Other methods do **not** return hypermedia unless you call one of the `hyper*` methods.


#### $server->hyperStatus($num, $str)

	class Foo extends \HummingJay\Resource{
		public function get($server){
			$server->hyperStatus(410, "This resource was removed forever.");
			return $server;
		}
	}


*Continue reading the following section as well for a more compact version of this same example.*

This example will return a response with an HTTP 410 status and the following message body:


	{
		"hypermedia": {
			"title": "410 Gone",
			"description": "This resource was removed forever."
		}
	}

You are free to use addResponseData() to provide more fine-grained responses to go along with the HTTP status code.


#### $server methods return instance of $server

All public API methods of the $server object return an instance of $server itself. This allows you to have more compact responses (which can make a big difference if you have a lot of guard statements). Here is an example using `hyperStatus()`:

	class Foo extends \HummingJay\Resource{
		public function get($server){
			return $server->hyperStatus(410, "This resource was removed forever.");
		}
	}

All of the method examples above can be thusly shortened. This feature also allows for the chaining of these methods. Feel free to experiment!

## Halting a resource with halt()

You can use a resource's constructor to act as a "guard" for the resource as a whole. This makes it possible to check for the validity of a request for all methods in one place.  To make a resource send its response immediately without any of the HTTP method handlers being invoked, simply call its `halt()` method.  Here's an example:

	class Foo extends Resource{
		public function __construct($server){
			$id = $server->params["foo_id"];
			if(!$db->isValidFoo($id)){
				$server->hyperStatus(404, "Could not find a foo with ID $id.");
				$this->halt();
			}
		}
	}

See the Books Demo (`demo/index.php`) for full examples of this behavior.


## Running Tests/Demo

If you have PHPUnit installed, you can run the unit tests with:

	phpunit

This project also includes a Vagrantfile, which you can use to install and provision a virtual machine complete with PHP 5.6, PHPUnit, and Apache webserver  configured to display the included HummingJay Demo website.  [Learn about Vagrant.](https://www.vagrantup.com/)

Once you have installed Vagrant, you can run the following commands to perform unit testing:

	> vagrant up
	> vagrant ssh
	$ cd /vagrant
	$ phpunit

With the virtual machine running, you can also visit the Demo website at http://localhost:8787/browser.html  *See note below!

The demo uses the catchily named [hm-json Browser](https://bitbucket.org/ratfactor/hm-json-browser/wiki/Home) to navigate a tiny demonstration Books collection API.


### Demo Note

Due to an *extremely* irritating setting hidden somewhere in the bowels of the Apache or PHP configuration, you will not be able to fully navigate the demo UNLESS you use this URL instead: http://localhost:8787/browser.html#/index.php

Apparently this version of Apache is configured to handle OPTIONS method requests all by itself unless it sees that mod_php will be handling the request (which it only determines if "index.php" is actually *in* the submitted URL).  This wonderful magical feature cost me valuable time with my family and I want it back.

I have no idea where the setting is. I've grepped and Googled to my wit's end.  Most of my time is spend chasing garbage like this down, not actually writing awesome code.  If anybody has a foolproof method of tracking down Apache's decision making process for something like this, I'd be delighted to hear about it.  Otherwise, let's all just light a candle in honor of the millions of completely wasted hours spent each year on these sorts of delightful web development secrets.



## License

The MIT License (MIT)

Copyright (c) 2015 David Gauer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


## Version History

I intend to stick to the rules of [semantic versioning](http://semver.org/).

Version | Date       | Description
--------|------------|------------
3.2.0   | 2015-12-08 | Added Added 'match-to-the-end' final param syntax
3.1.0   | 2015-11-20 | Added $decodeJson option to Resource object
3.0.2   | 2015-08-12 | Resource::options() tested and refactored, fixed bug in extractApiUri()
3.0.1   | 2015-07-29 | Minor bugfix, completed test coverage
3.0.0   | 2015-07-25 | Major refactor with API-breaking changes (cleaner, more testable)
2.0.1   | 2015-07-24 | Added Vagrant VM for testing/demo, refactored and added more tests
2.0.0   | 2015-07-18 | Refactored, added Request class, changed interface of request payload
1.1.1   | 2015-06-19 | Removed dev-only dependency for PHPUnit (don't install via Composer)
1.1.0   | 2015-06-19 | Added JSON-decoded payload to the request object, updated README
1.0.0   | 2015-03-01 | HummingJay is in use in real projects
0.1.1   | 2015-02-13 | Removed bug from early prototyping leftover
0.1.0   | 2015-02-10 | Intial release. Replaces previous project, "hm-json ResourcePhp"
