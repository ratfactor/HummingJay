# HummingJay

## Overview

HummingJay is a PHP 5.5+ library for creating REST APIs using the **hm-json** (todo: hm-json needs a stand-alone spec page!) format to deliver hypermedia and JSON data to clients. It has methods for routing, hypermedia generation, and handling HTTP methods and HTTP status codes.

## Tiny Example

	<?php
	require "vendor/autoload.php";

	class Foo extends HummingJay\Resource{}

	$api = new HummingJay\HummingJay("/foo - Foo");
	?>

This example is unrealistically tiny.  But it does show the two basic elements of creating an API with HummingJay: creating Resource classes ("Foo") and defining URI paths ("/foo") to resolve to those classes - also known as "routing".

Look in the source at the heavily commented `demo/index.php` file for a much more thorough example.

## Routing

The constructor of the `HummingJay\HummingJay` class takes a string containing a list of routes.  The format is very simple: the routes are separated by newlines ("\n") and the URI of each route is separated from its associated Resource name by a hypen or dash ("-").

Here's an example using PHP's nowdoc string format:

	$api = new HummingJay\HummingJay(<<<'API'
	/ - MyRoot
	/foo - Foo
	/foo/bars - BarCollection
	/foo/bars/{bar_id} - SpecificBar
	API
	);

Here you can see that we've defined four possible URIs.  One of them, `/foo/bars/{bar_id}`, has a parameter, which will match URIs such as `/foo/bars/31` and `/foo/bars/Cheers`. Each will be handled by an instance of the resource class specified (e.g. `/foo` is handled by the `Foo` class). 

## Creating Resources

To create a new resource, extend the `HummingJay\Resource` class.  Example:

	class Foo extends HummingJay\Resource{
		$title = "The Foo Resource!";
		$description = "I don't do much.  Try a GET to get a list of stuff!";
	}

As shown, it is recommended that you also customize the public title and description.  These will be viewable by any application (such as hm-json Browser) which can read hm-json formatted hypermedia.

Then add as many HTTP method handlers as you would like (supported: OPTIONS, GET, PUT, POST, DELETE, PATCH, HEAD).  Here's a GET which will return a JSON-encoded object containing a parameter named "foo_id".

	class Foo extends HummingJay\Resource{
		// ...

		public function get($req, $res){
			$res->addData(["foo_id"=>"1003"]);
			return $res;
		}
	}
	
Keep reading for more explanation about this handler example.

_Note: HummingJay provides a default OPTIONS method and you may want to call it for the default hypermedia behavior. See the Book and ReviewsCollection resource classes in demo/index.php for examples of this._

### The $req (request) parameter for method handlers

The first parameter, conventionally named `$req`, contains information about the HTTP request:

Property      | Description
--------------|---------------------------------
__uri__           | the API URI matched by your resource
__params__        | an associative array of parameters from the URI
__method__        | the HTTP method used, e.g. POST
__resource_uris__ | a list of the other URIs in your API - generally used by the supplied default OPTIONS handler

Here's an example of getting the parameters of a URI:

	// route: /foo/bar/{bar_id}
	// actual URI: /foo/bar/74
	
	$mybar = $req->params['bar_id']; 
	// $mybar = 74

### The $res (response) parameter for method handlers

The '$res' parameter is a Response object which you may manipulate and then return as the output of your method.

In the above example, the GET method uses the addData() method to add JSON-encoded data to the response.  Response defines a simple API as seen in this example:

	class Foo extends HummingJay\Resource{
		public function get($req, $res){
			$res->hyperTitle("Your data"); // sets the hm-json title
			$res->hyperDescription("This data is great!");
			$res->hyperLink([
				"title" => "Cool Link!",
				"href" => "/cool/uri",
				"rel" => "other"
			]);
			$res->addData($data);
		}
	}

As soon as you add any of the hypermedia properties such as title, description, or hyperlink, the Response object knows to return hm-json hypermedia.  It is not returned by default expect by the default OPTIONS method handler.

You can see that hyperlinks are simply PHP arrays with three specific named values.  You can add as many hyperlinks as you'd like to your response's hypermedia.

## Returning Errors
At any time, you can call the following static methods to immediately send an HTTP error response with an explanatory string.

	$res->send400("Your request is so bad!");
	$res->send404("Couldn't find that thing!");
	$res->send405("Don't POST to this resource!");
	$res->send500("Argh! It hurts so bad!");

## Formatting response data


TODO!!


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
1.0.0   | 2015-03-01 | HummingJay is in use in real projects
0.1.1   | 2015-02-13 | Removed bug from early prototyping leftover
0.1.0   | 2015-02-10 | Intial release. Replaces previous project, "hm-json ResourcePhp"
