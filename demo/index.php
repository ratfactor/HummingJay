<?php

/*
	NOTE: You can run this demo through the browser within a Vagrant virtual
	machine. See the Vagrantfile at the root of the project.
*/


/*
	Realistically, your modern project is likely to use namespacing, so this
	demo does as well (Demo). HummingJay does not require it.

	Unrealistically, this demo contains everything in one file. It's much more
	likely that you'll have your Resource classes in their own files.
*/ 

namespace Demo;


/*
	Attempt to turn on error reporting for the demo
*/

ini_set('display_errors', 1);
error_reporting(E_ALL);


/*
	Use the Composer-supplied autoloader to gain access to HummingJay's classes.
	NOTE: You'll need to have composer generate the autoloader first:
		composer install
*/ 

require_once "../vendor/autoload.php";


/*
	For the sake of brevity below, I am 'use'-ing classes from 
	HummingJay. You could also use fully qualified names for the classes
	like so:
		class Book extends \HummingJay\Resource{ ... }
*/

use HummingJay\Resource;
use HummingJay\Server;
use HummingJay\HummingJay;


/*
	The FakeBookDb class exists for demo purposes only. As you can see,
	it's just an array and a getBook() method to supply us with data.
*/

class FakeBookDb{
	public static $books = array(
		57=>array("id"=>"57", "title"=>"Pride and Prejudice",   "author"=>"Jane Austen"),
		64=>array("id"=>"64", "title"=>"The Lord of the Rings", "author"=>"JRR Tolkien"),
		71=>array("id"=>"71", "title"=>"Jane Eyre",             "author"=>"Charlotte Bronte"),
		76=>array("id"=>"76", "title"=>"Wuthering Heights",     "author"=>"Emily Bronte"),
		82=>array("id"=>"82", "title"=>"Great Expectations",    "author"=>"Charles Dickens"),
		98=>array("id"=>"98", "title"=>"Catch 22",              "author"=>"Joseph Heller")
	);

	public static function getBook($id){
		if(!array_key_exists($id, self::$books)){
			return NULL;
		}
		return self::$books[$id];
	}

	public static function getAuthors(){
		$authors = array();
		foreach(self::$books as $b){
			array_push($authors, $b['author']);
		}
		return $authors;
	}
}


/*
	Here is the first resource defined in our demo API - the Root element.  
	You can scroll all the way to the bottom of this file to see how the 
	paths are defined. Root corresponds with the "/" resource path.

	This example is about as simple as it gets. Overriding the default
	title and description are not required, but certainly recommended!
*/

class Root extends Resource{
	public $title = "Books API Root";
	public $description = "Welcome to the HummingJay Books Demo!";
}


/*
	BooksCollection is a little more interesting.  It supplies handlers for GET
	and POST methods. GET returns a list of links to the existing books.  
	Providing lists of items in hm-json link format is not required, but is
	very friendly as it allows programs (or people using the hm-json Browser)
	to continue traversing your API in an automated fashion.

	By default, methods other than OPTIONS do not return hm-json hypermedia
	data.

	The POST method is clearly just a fake stub as the database cannot be 
	modified in this demo.
*/

class BooksCollection extends Resource{
	public $title = "Books Collection";
	public $description = "POST a new book or GET a list of books (in hm-json link format).";

	public function get($server){
		foreach(FakeBookDb::$books as $id=>$book){
			$server->hyperLink([
				"title" => $book['title'],
				"href" => "/books/$id",
				"rel" => 'item'
			]);
		}
		return $server;
	}

	public function post($server){
		if($server->requestData){
			$server->addData(["new_id"=>100]); // pretend it worked
		}
		return $server;
	}
}


/*
	The Book resource will be supplied with a book_id parameter. It is 
	available to the method handlers in the $server object supplied to the 
	methods in the params hash (array):

		$server->params["book_id"]

	getBook() is used by the method handlers to determine if the book exists and 
	can override any other behavior if the book does not exist by immediately 
	returning an HTTP 404 error message.  HummingJay tries to be as agnostic
	as possible about the structure of your program and this is only an example.

	The Book resource also demonstrates overriding the OPTIONS method handler to
	check for the existance of the requested book and to update the title of the
	hm-json return data to make it specific to the book.  Notice that the
	default handler is used to generate the boilerplate parent/child links, etc.
	which would otherwise be tedious to re-create by hand.  Then just the 
	hm-json title property (via hyperTitle()) of the generated reponse is 
	altered to reflect the book's actual title.

	You can see how simple the GET method handler is. A request for URI

		/books/57

	results in the following response from the API:

		{
		    "id": "57",
		    "title": "Pride and Prejudice",
		    "author": "Jane Austen"
		}	

	To see why I've made a public data() method, continue scrolling down to 
	the ReviewsCollection class.
*/

class Book extends Resource{
	public $title = "A Book";
	public $description = "You can GET this book's data, POST updates, or DELETE it.";
	private $book = NULL;

	public function __construct($server){
		$id = $server->params["book_id"];
		$this->book = FakeBookDb::getBook($id);
		if(is_null($this->book)){
			Server::send404("Could not find a book with ID $id.");
		}
	}

	public function options($server){
		$server = parent::options($server);
		$server->hyperTitle($this->book['title']);
		return $server;
	}

	public function get($server){
		$server->addData($this->book);
		return $server;
	}

	public function data(){
		return $this->book;
	}
}


/*
	The reviews collection for a book demonstrates the openness of the program
	architecture - the reviews for a book queries the Book resource for a book
	by the given ID (using the $server object).  This allows the check for 
	a valid book with an ID (and generating a 404 error if not found, etc.) to 
	be kept in one place.  

	HummingJay does not provide anything to either help or hinder this sort of 
	interaction between your resources.  This is merely "food for thought."

	The book reviews themselves don't demonstrate anything interesting.
*/

class ReviewsCollection extends Resource{
	public $title = "Reviews for a book";
	public $description = "GET the list of reviews for this book.";

	public function options($server){
		$book = new Book($server);
		$server = parent::options($server);
		$server->hyperTitle("Reviews for ".$book->data()['title']);
		return $server;
	}

	public function get($server){
		// generate fake links
		foreach(["Love it", "Boring!", "Thoughtful", "My Favorite book"] as $title){
			$link_uri = $server->uri."/".rand(10,60);
			$server->hyperLink(["title" => $title, "href" => $link_uri, "rel" => 'item']);
		}
		return $server;
	}
}

class Review extends Resource{
	public $title = "Book Review";
	public $description = "GET this resource for the full data.";

	public function get($server){
		$server->addData([
			"book_id"=>$server->params["book_id"],
			"review_author"=>"John Doe",
			"title"=>"This book is great!",
			"content"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit...",
			"rating"=>3
		]);
		return $server;
	}
}


/*
	The authors collection only exists so that we can have another top-level
	item (/authors) under the root (/) of the API.
*/

class AuthorsCollection extends Resource{
	public $title = "Authors";
	public $description = "You can GET a list of the authors of all of the books available through this API.";

	public function get($server){
		$server->addData(["authors_list"=>FakeBookDb::getAuthors()]);
		return $server;
	}
}


/*
	And finally, here's the fun part. The paths are defined in an extremely
	simple text format:

	  /uri/to/resource - ResourceClassName

	As you can see, the class names below are defined using a namespace of 
	Demo.  These are fully-qualified names - the preceeding slash is not needed
	when classes are instantiated using a string name for reasons which, quite 
	frankly, confuse the heck out of me, but apparently made sense to the PHP 
	developers.

	In a normal project, this is probably one of the only things which would
	actually exist in this file - the Resource classes would typically be in
	their own files.
*/

$api = new HummingJay(<<<'API'
/ - Demo\Root
/books - Demo\BooksCollection
/books/{book_id} - Demo\Book
/books/{book_id}/reviews - Demo\ReviewsCollection
/books/{book_id}/reviews/{review_id} - Demo\Review
/authors - Demo\AuthorsCollection
API
);

