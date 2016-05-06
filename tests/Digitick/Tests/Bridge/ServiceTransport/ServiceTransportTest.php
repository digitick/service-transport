<?php

namespace Digitick\Tests\Bridge\ServiceTransport;

use Digitick\Bridge\ServiceTransport\NetworkTransportInterface;
use Digitick\Bridge\ServiceTransport\CircuitBreakerInterface;
use Digitick\Bridge\ServiceTransport\Request\RequestArgument;
use Digitick\Bridge\ServiceTransport\Request\RequestArguments;
use Digitick\Bridge\ServiceTransport\ServiceTransport;
use Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException;
use Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException;
use Digitick\Bridge\ServiceTransport\Exception\Serialization\SerializationException;
use Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException;
use Digitick\Bridge\ServiceTransport\ServiceSerializerInterface;
use Prophecy\Argument;

class ServiceTransportTest extends \PHPUnit_Framework_TestCase
{
    const NETWORK_INTERFACE = 'Digitick\Bridge\ServiceTransport\NetworkTransportInterface';
    const CIRCUIT_BREAKER_INTERFACE = 'Digitick\Bridge\ServiceTransport\CircuitBreakerInterface';
    const SERIALIZER_INTERFACE = 'Digitick\Bridge\ServiceTransport\ServiceSerializerInterface';

    private $baseUrl = "http://api.myapp.com";
    private $endpoint = "/domain/service/method";
    private $arguments;

    protected function setUp () {
        $this->arguments = new RequestArguments(1);
        $this->arguments[0] = new RequestArgument('arg1', 'value1');
    }

    public function allMethodsProvider () {
        return array_merge(
            $this->noBodyProvider(),
            $this->withBodyProvider()
        );
    }

    public function noBodyProvider () {
        return [
            ['retrieve', null],
            ['delete', null],
        ];
    }

    public function withBodyProvider () {
        return [
            ['retrieve', null],
            ['delete', null],
        ];
    }

    protected function buildCBAvailable () {
        $circuitBreaker = $this->prophesize(self::CIRCUIT_BREAKER_INTERFACE);
        $circuitBreaker->isAvailable (Argument::any())
            ->shouldBeCalled()
            ->willReturn (true)
        ;
        $circuitBreaker->reportSuccess (Argument::any())
            ->shouldBeCalled()
        ;

        return $circuitBreaker;
    }

    protected function buildCBAvailableNoSuccess () {
        $circuitBreaker = $this->prophesize(self::CIRCUIT_BREAKER_INTERFACE);
        $circuitBreaker->isAvailable (Argument::any())
            ->shouldBeCalled()
            ->willReturn (true)
        ;

        return $circuitBreaker;
    }

    protected function buildCBAvailableReportFailure () {
        $circuitBreaker = $this->prophesize(self::CIRCUIT_BREAKER_INTERFACE);
        $circuitBreaker->isAvailable (Argument::any())
            ->shouldBeCalled()
            ->willReturn (true)
        ;

        $circuitBreaker->reportFailure (Argument::any())
            ->shouldBeCalled()
        ;

        return $circuitBreaker;
    }

    protected function buildCBUnavailable () {
        $circuitBreaker = $this->prophesize(self::CIRCUIT_BREAKER_INTERFACE);
        $circuitBreaker->isAvailable (Argument::any())
            ->shouldBeCalled()
            ->willReturn (false)
        ;
        $circuitBreaker->reportFailure (Argument::any())
        ;

        return $circuitBreaker;
    }

    protected function buildSerializer ($serializeReturn = null, $unserializeReturn = null) {
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);

        if ($serializeReturn !== null) {
            $serializer->serialize (Argument::any())
                ->shouldBeCalled()
                ->willReturn ($serializeReturn)
                ;
        }

        if ($unserializeReturn !== null) {
            $serializer->unserialize (Argument::any())
                ->shouldBeCalled()
                ->willReturn ($unserializeReturn)
                ;
        }

        return $serializer;
    }

    protected function buildSerializerBadUnserialize ($serializeReturn = null, $unserializeReturn = null) {
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);


        $serializer->unserialize (Argument::any())
            ->shouldBeCalled()
            ->willThrow (new SerializationException())
        ;

        return $serializer;
    }

    protected function buildNetwork ($method, $response) {
        $network = $this->prophesize(self::NETWORK_INTERFACE);
        $network->$method($this->endpoint, $this->arguments, Argument::cetera())
            ->shouldBeCalled()
            ->willReturn ($response)
        ;
        $network->getBaseUrl (Argument::any())
            ->willReturn ($this->baseUrl)
        ;

        return $network;
    }

    protected function buildNetworkNotFound($method)
    {
        $network = $this->prophesize(self::NETWORK_INTERFACE);
        $network->$method($this->endpoint, $this->arguments, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow (new NotFoundException())
        ;
        $network->getBaseUrl (Argument::any())
            ->willReturn ($this->baseUrl)
        ;

        return $network;
    }

    protected function buildNetworkForbidden($method)
    {
        $network = $this->prophesize(self::NETWORK_INTERFACE);
        $network->$method($this->endpoint, $this->arguments, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow (new ForbiddenException())
        ;
        $network->getBaseUrl (Argument::any())
            ->willReturn ($this->baseUrl)
        ;

        return $network;
    }

    protected function buildNetworkUnavailable($method)
    {
        $network = $this->prophesize(self::NETWORK_INTERFACE);
        $network->$method($this->endpoint, $this->arguments, Argument::cetera())
            ->shouldBeCalled()
            ->willThrow (new TransportUnavailableException())
        ;
        $network->getBaseUrl (Argument::any())
            ->willReturn ($this->baseUrl)
        ;

        return $network;
    }


    /**
     * @dataProvider allMethodsProvider
     */
    public function testBasic ($method, $data) {


        $serviceResponse = $method . 'tonton';
        $unserializedResponse = $method . 'tonton_unserialized';

        $network = $this->buildNetwork($method, $serviceResponse);
        $circuitBreaker = $this->buildCBAvailable();
        $serializer = $this->buildSerializer($data, $unserializedResponse);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

        $this->assertEquals($unserializedResponse, $response);
    }

    /**
     * @dataProvider allMethodsProvider
     */
    public function testWithoutCircuitBreaker ($method, $data) {
        $serviceResponse = 'tonton';
        $unserializedResponse = 'tonton_unserialized';

        $network = $this->buildNetwork($method, $serviceResponse);
        $serializer = $this->buildSerializer($data, $unserializedResponse);

        $service = new ServiceTransport(
            $network->reveal(),
            null,
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

        $this->assertEquals($unserializedResponse, $response);
    }

    /**
     * @dataProvider allMethodsProvider
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\ServiceUnavailableException
     */
    public function testCircuitBreakerUnavailable ($method, $data) {
        $serviceResponse = 'tonton';
        $unserializedResponse = 'tonton_unserialized';

        $network = $this->prophesize(self::NETWORK_INTERFACE);
        $circuitBreaker = $this->buildCBUnavailable();
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

    }

    /**
     * @dataProvider allMethodsProvider
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException
     */
    public function testNetworkNotFoundException ($method, $data) {

        $network = $this->buildNetworkNotFound($method);
        $circuitBreaker = $this->buildCBAvailableNoSuccess();
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

    }

    /**
     * @dataProvider allMethodsProvider
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException
     */
    public function testNetworkForbiddenException ($method, $data) {

        $network = $this->buildNetworkForbidden($method);
        $circuitBreaker = $this->buildCBAvailableNoSuccess();
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }
    }

    /**
     * @dataProvider allMethodsProvider
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\ServiceUnavailableException
     */
    public function testNetworkUnavailable ($method, $data) {
        $network = $this->buildNetworkUnavailable($method);
        $circuitBreaker = $this->buildCBAvailableReportFailure();
        $serializer = $this->prophesize(self::SERIALIZER_INTERFACE);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }
    }

    /**
     * @dataProvider allMethodsProvider
     */
    public function testGoodJsonResponse ($method, $data) {
        $serviceResponse = '{
  "userId": 1,
  "id": 1,
  "title": "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
  "body": "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\n"
}';
        $unserializedResponse = [
            'userId' => 1,
            'id' => 1,
            'title' => 'sunt aut facere repellat provident occaecati excepturi optio reprehenderit',
            'body' => 'quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\n'
        ];

        $network = $this->buildNetwork($method, $serviceResponse);
        $circuitBreaker = $this->buildCBAvailable();
        $serializer = $this->buildSerializer($data, $unserializedResponse);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

        $this->assertEquals($unserializedResponse, $response);
    }

    /**
     * @dataProvider noBodyProvider
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\UnexpectedResponseException
     */
    public function testBadJsonResponse ($method, $data) {
        $serviceResponse = '{
  "userId": 1,
  "id": 1,
  "title": "sunt aut facere repellat provident occaecati excepturi optio reprehenderit",
  "body": "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\n"
}';
        $unserializedResponse = [
            'userId' => 1,
            'id' => 1,
            'title' => 'sunt aut facere repellat provident occaecati excepturi optio reprehenderit',
            'body' => 'quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\n'
        ];

        $network = $this->buildNetwork($method, $serviceResponse);
        $circuitBreaker = $this->buildCBAvailableNoSuccess();
        $serializer = $this->buildSerializerBadUnserialize($data, $unserializedResponse);

        $service = new ServiceTransport(
            $network->reveal(),
            $circuitBreaker->reveal(),
            $serializer->reveal()
        );

        if ($data == null ) {
            $response = $service->$method($this->endpoint, $this->arguments);
        } else {
            $response = $service->$method($this->endpoint, $this->arguments, $data);
        }

        $this->assertEquals($unserializedResponse, $response);
    }
}
