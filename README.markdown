# HummingJay

## Overview

HummingJay is a PHP 5.3+ library for creating REST APIs using the **hm-json** (todo: hm-json needs a stand-alone spec page) format to deliver hypermedia and JSON data to clients. It has methods for routing, hypermedia generation, and handling HTTP methods and HTTP status codes.

## Example

	<?php
		require "vendor/autoload.php";
		$myApi = new HummingJay\HummingJay();
	?>

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
0.1.0   | 2015-01-22 | Project started, will eventually replace my previous attempt, "hm-json ResourcePhp"