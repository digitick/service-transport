<?php


namespace Digitick\Tests\Bridge\ServiceTransport\Request;


use Digitick\Bridge\ServiceTransport\Request\RequestArgument;

class RequestArgumentTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct ()
    {
        $argument = new RequestArgument('arg', 'value');

        $this->assertEquals("arg", $argument->getName());
        $this->assertEquals("value", $argument->getValue());
    }

    public function testSetters ()
    {
        $argument = new RequestArgument('arg', 'value');
        $argument->setName('argNew');
        $argument->setValue('valueNew');

        $this->assertEquals("argNew", $argument->getName());
        $this->assertEquals("valueNew", $argument->getValue());
    }
}
