<?php
namespace HummingJay;

class Foo extends Resource{
    // For testMakeResource 
} 






/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-06-19 at 21:48:05.
 */
class HummingJayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HummingJay
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new HummingJay();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers HummingJay\HummingJay::parseRouteString
     * @todo   Implement testParseRouteString().
     */
    public function testParseRouteString()                                            
    {
        $this->assertArrayHasKey(
            "/books",
            $this->object->parseRouteString("/books - Demo\\BooksCollection") , 
            "Test a single line route string"
        );

        $this->assertArrayHasKey(
            "/cars",
            $this->object->parseRouteString("/books - D \n/cars - C") , 
            "Test two line route string with UNIX line ending"
        );

        $this->assertArrayHasKey(
            "/cars",
            $this->object->parseRouteString("/books - D \r\n/cars - C"),
            "Test two line route string with Windows line ending"
        );

        $this->assertArrayHasKey(
            "/books",
            $this->object->parseRouteString("/books - D"),
            "Test single line route string."
        );

    }

    
    /**
    * @expectedException UnexpectedValueException
    */
    public function testParseRouteStringException()
    {
        //Bad route string because no class for route /books.
        $this->object->parseRouteString("/books");
        
    }
        

    /**
     * @covers HummingJay\HummingJay::matchUri
     */
    public function testMatchUri()
    {
        $routes = $this->object->parseRouteString("/books - D \r\n/cars - C");

        $this->assertEquals(
            null, 
            $this->object->matchUri($routes, '/cats'), 
            "Test if a URI that doesn't match returns null"
        );

        $this->assertEquals(
            'D', 
            $this->object->matchUri($routes, '/books')["classname"], 
            "Test if a URI match returns classname"
        );

        $this->assertEquals(
            'C', 
            $this->object->matchUri($routes, '/cars')["classname"], 
            "Test if a URI match returns classname on a second item"
        );

        $routes = $this->object->parseRouteString("/books/{bid}/reviews/{rid} - Review");
        $matchedResource = $this->object->matchUri($routes, '/books/13/reviews/45'); 

        $this->assertEquals('13', $matchedResource['params']['bid'], "URI match has first param");
        $this->assertEquals('45', $matchedResource['params']['rid'], "URI match has second param");

    }



    /**
     * @covers HummingJay\HummingJay::makeResource
     */
    public function testMakeResource()
    {
        $req = new Request('');

        $this->assertEquals(
            null, 
            $this->object->makeResource("NoGoats", $req), 
            "Class that doesn't exist returns null"
        );
// todo add a test to test your test in a postive class existing on this plane of existance.

        $this->assertInstanceOf(
            "HummingJay\Foo", 
            $this->object->makeResource("HummingJay\Foo", $req),
            "Check if a Foo object is returned."
        );


    }

}
