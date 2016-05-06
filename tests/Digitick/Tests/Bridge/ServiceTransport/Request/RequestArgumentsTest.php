<?php


namespace Digitick\Tests\Bridge\ServiceTransport\Request;


use Digitick\Bridge\ServiceTransport\Request\RequestArguments;
use Digitick\Bridge\ServiceTransport\Request\RequestArgument;
use InvalidArgumentException;

class RequestArgumentsTest extends \PHPUnit_Framework_TestCase
{
    const REQUEST_ARGUMENT_CLASS = "Digitick\\Bridge\\ServiceTransport\\Request\\RequestArgument";

    public function testNominal ()
    {
        $arguments = new RequestArguments(3);

        $arguments[0] = new RequestArgument('arg1', 'value1');
        $arguments[1] = new RequestArgument('arg2', 'value2');
        $arguments[2] = new RequestArgument('arg3', 'value3');

        $this->assertInstanceOf(self::REQUEST_ARGUMENT_CLASS, $arguments[0]);
        $this->assertEquals("arg1", $arguments[0]->getName());
        $this->assertEquals("value1", $arguments[0]->getValue());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testBadTypeString ()
    {

        $arguments = new RequestArguments(3);

        $arguments[0] = 'tonton';
    }

    public function testFromEmptyArray () {
        $collection = RequestArguments::fromArray([]);

        $this->assertCount(0, $collection);
    }

    public function testFromArrayContent ()
    {
        $array  = [
            new RequestArgument('arg1', 'value1'),
            new RequestArgument('arg2', 'value2'),
            new RequestArgument('arg3', 'value3')
        ];


        $collection = RequestArguments::fromArray($array);

        $this->assertInstanceOf (self::REQUEST_ARGUMENT_CLASS, $collection[0]);
        $this->assertInstanceOf (self::REQUEST_ARGUMENT_CLASS, $collection[1]);
        $this->assertInstanceOf (self::REQUEST_ARGUMENT_CLASS, $collection[2]);

        $this->assertEquals("arg1", $collection[0]->getName());
        $this->assertEquals("value1", $collection[0]->getValue());
        $this->assertEquals("arg2", $collection[1]->getName());
        $this->assertEquals("value2", $collection[1]->getValue());
        $this->assertEquals("arg3", $collection[2]->getName());
        $this->assertEquals("value3", $collection[2]->getValue());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFromArrayBadType ()
    {
        $array  = [
            new RequestArgument('arg1', 'value1'),
            "tonton",
            new RequestArgument('arg3', 'value3')
        ];


        $collection = RequestArguments::fromArray($array);
    }

    public function testFromArrayCount ()
    {
        $array  = [
            new RequestArgument('arg1', 'value1'),
            new RequestArgument('arg2', 'value2'),
            new RequestArgument('arg3', 'value3')
        ];

        $collection = RequestArguments::fromArray($array);

        $this->assertCount (3, $collection);
    }

    public function testFromArraySaveIndexes ()
    {
        $array  = [
            new RequestArgument('arg1', 'value1'),
            new RequestArgument('arg3', 'value3'),
            new RequestArgument('arg2', 'value2')
        ];

        $collection = RequestArguments::fromArray($array, true);

        $this->assertCount (3, $collection);

        $this->assertEquals("arg1", $collection[0]->getName());
        $this->assertEquals("value1", $collection[0]->getValue());
        $this->assertEquals("arg3", $collection[1]->getName());
        $this->assertEquals("value3", $collection[1]->getValue());
        $this->assertEquals("arg2", $collection[2]->getName());
        $this->assertEquals("value2", $collection[2]->getValue());
    }

    public function testFromArraySaveIndexesOutbound ()
    {
        $array  = [
            1 => new RequestArgument('arg1', 'value1'),
            2 => new RequestArgument('arg3', 'value3'),
            3 => new RequestArgument('arg2', 'value2')
        ];

        $collection = RequestArguments::fromArray($array, true);

        $this->assertCount (3, $collection);

        $this->assertEquals("arg1", $collection[0]->getName());
        $this->assertEquals("value1", $collection[0]->getValue());
        $this->assertEquals("arg3", $collection[1]->getName());
        $this->assertEquals("value3", $collection[1]->getValue());
        $this->assertEquals("arg2", $collection[2]->getName());
        $this->assertEquals("value2", $collection[2]->getValue());
    }

    public function testToArray ()
    {
        $array  = [
            new RequestArgument('arg1', 'value1'),
            new RequestArgument('arg2', 'value2'),
            new RequestArgument('arg3', 'value3')
        ];

        $collection = RequestArguments::fromArray($array);

        $arr = $collection->toArray();

        $this->assertArrayHasKey ('arg1', $arr);
        $this->assertArrayHasKey ('arg2', $arr);
        $this->assertArrayHasKey ('arg3', $arr);

        $this->assertEquals('value1', $arr['arg1']);
        $this->assertEquals('value2', $arr['arg2']);
        $this->assertEquals('value3', $arr['arg3']);

    }
}
