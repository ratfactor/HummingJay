<?php

/*
	NOTE: You can run this demo through the browser within a Vagrant virtual
	machine. See the README and Vagrantfile at the root of the project.
*/





/*
	Realistically, your modern project is likely to use namespacing, so this
	demo does as well. HummingJay does not require it.

	Unrealistically, this demo contains everything in one file. It's much more
	likely that you'll have your resource classes in their own files.
*/ 

namespace Demo;


/*
	Attempt to turn on error reporting for the demo
*/

ini_set('display_errors', 1);
error_reporting(E_ALL);


/*
	Manually include the class files.

	You can use Composer to install HummingJay and let its autoloader do 
	this for you.
*/ 

require_once "../src/HummingJay.php";
require_once "../src/Resource.php";
require_once "../src/Server.php";


/*
	For the sake of brevity, I am 'use'-ing classes from HummingJay. 
	You could also use fully qualified names for the classes like so:
		class Book extends \HummingJay\Resource{ ... }
*/

use HummingJay\Resource;
use HummingJay\Server;
use HummingJay\HummingJay;


/*
	The FakeBookDb class exists for demo purposes only. As you can see,
	it's just an array and a getBook() method to supply us with data.

	This has nothing to do with HummingJay and everything to do with the demo.
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
	routes are defined. Root corresponds with the "/" resource URI.

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

	Methods other than the supplied OPTIONS handler do not return hm-json hypermedia
	data by default.

	The POST method is just a fake stub since the database cannot be meaningfully
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
		if($server->requestData){ // make sure the user sent something valid
			$server->addResponseData(["new_id"=>100]); // pretend it worked
		}
		return $server;
	}
}



/*
	The Book resource will be supplied with a book_id parameter via the params
	array in the $server object, which is sent to every method handler as well
	as the constructor for each Resource.

		$server->params["book_id"]

	FakeBookDb::getBook() is used by the resource's constructor to determine if 
	the book exists. If it does not, it sets the HTTP response status to 404 and
	then calls the Resource method halt() to prevent any method handlers from
	being called.

	HummingJay tries to be as agnostic as possible about the structure of your 
	program and this is only an example.

	The Book resource also demonstrates overriding the OPTIONS method handler to
	check for the existance of the requested book and to update the title of the
	hm-json return data to make it specific to the book.  Notice that the
	default handler is used to generate the boilerplate parent/child links, etc.
	which would otherwise be tedious to re-create by hand.

	You can see how simple the GET method handler is. A request for URI

		/books/57

	results in the following response from the API:

		{
		    "id": "57",
		    "title": "Pride and Prejudice",
		    "author": "Jane Austen"
		}	

	To see what else makes this class interesting, continue to ReviewsCollection
*/

class Book extends Resource{
	public $title = "A Book";
	public $description = "You can GET this book's data, POST updates, or DELETE it.";
	public $data = NULL;

	public function __construct($server){
		$id = $server->params["book_id"];
		$this->data = FakeBookDb::getBook($id);
		if(is_null($this->data)){
			$server->hyperStatus(404, "Could not find a book with ID $id.");
			$this->halt();
		}
	}

	public function options($server){
		$server = parent::options($server);
		$server->hyperTitle($this->data['title']);
		return $server;
	}

	public function get($server){
		$server->addResponseData($this->data);
		return $server;
	}
}


/*
	ReviewsCollection relies on the Book class to look up a book. This allows the 
	code to remain as D.R.Y. as possible.  Book even handles setting the 404.

	Review does the same thing.

	HummingJay does not provide anything to either help or hinder this sort of 
	interaction between your resources.  This is merely "food for thought."
*/

class ReviewsCollection extends Resource{
	public $title = "Reviews for a book";
	public $description = "GET the list of reviews for this book.";
	private $book = null;

	public function __construct($server){ 
		$this->book = new Book($server); 
		if(!$this->book->data){ $this->halt(); }
	}

	public function options($server){
		$server = parent::options($server); // generate hypermedia, etc.
		$server->hyperTitle("Reviews for ".$this->book->data['title']);
		return $server;
	}

	public function get($server){
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
	private $book = null;

	public function __construct($server){ 
		$this->book = new Book($server); 
		if(!$this->book->data){ $this->halt(); }
	}

	public function get($server){
		$server->addResponseData([
			"book_id"=>$this->book->data['id'],
			"book_title"=>$this->book->data['title'],
			"review_author"=>"John Doe",
			"review_title"=>"This book is great!",
			"content"=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit...",
			"rating"=>rand(1,5)
		]);
		return $server;
	}
}


/*
	The AuthorsCollection only exists so that we can have another top-level
	resource URI "/authors".  Otherwise the root would look empty and sad.
*/

class AuthorsCollection extends Resource{
	public $title = "Authors";
	public $description = "You can GET a list of the authors of all of the books available through this API.";

	public function get($server){
		$server->addResponseData(["authors_list"=>FakeBookDb::getAuthors()]);
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

