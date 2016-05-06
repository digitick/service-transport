<?php
namespace Digitick\Tests\Bridge\ServiceTransport\HttpTransport;

use Digitick\Bridge\ServiceTransport\HttpTransport\GuzzleHttpTransport;
use Digitick\Bridge\ServiceTransport\Request\RequestArgument;
use Digitick\Bridge\ServiceTransport\Request\RequestArguments;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class GuzzleHttpTransportTest extends \PHPUnit_Framework_TestCase
{
    protected function createMock ($stringResponse, $status = 200, $baseUrl = 'http://api.myapp.com') {

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock([
            new Response($status, [], Stream::factory($stringResponse))
        ]);
        $httpClient->getEmitter()->attach($httpMock);
        return $httpClient;
    }

    public function testBaseUrl () {
        $expected = 'http://api.myapp.com';
        $httpClient = $this->createMock('foo', 200, $expected);
        $transport = new GuzzleHttpTransport($httpClient);
        $baseUrl = $transport->getBaseUrl();

        $this->assertEquals($expected, $baseUrl);
    }

    public function testRetrieve () {
        $expectedResponse = 'Retrieve Successful';
        $httpClient = $this->createMock($expectedResponse, 200, 'http://api.myapp.com');
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('api.myapp.com', $transport->getLastRequestHost());
        $this->assertEquals(80, $transport->getLastRequestPort());
        $this->assertEquals('get', strtolower ($transport->getLastRequestMethod()));
        $this->assertEquals('http://api.myapp.com/tonton', $transport->getLastRequestUrl());
        $this->assertEquals('', $transport->getLastRequestBody());
    }

    public function testRetrieveEmptyMessage () {
        $expectedResponse = '';
        $httpClient = $this->createMock($expectedResponse);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException
     */
    public function testRetrieveNotFound () {
        $expectedResponse = 'Retrieve not found';
        $httpClient = $this->createMock($expectedResponse, 404);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException
     */
    public function testRetrieveForbidden () {
        $expectedResponse = 'Retrieve forbidden';
        $httpClient = $this->createMock($expectedResponse, 403);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testRetrieveError5xx () {
        $expectedResponse = 'Retrieve exception';
        $httpClient = $this->createMock($expectedResponse, 500);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testRetrieveConnectionTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new ConnectException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testRetrieveReadTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new RequestException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->retrieve('/tonton', new RequestArguments());
    }

    public function testRetrieveHeaders () {
        $expectedResponse = 'Retrieve Successful';
        $httpClient = $this->createMock($expectedResponse);

        $transport = new GuzzleHttpTransport($httpClient);
        $transport->retrieve('/tonton', new RequestArguments());

        $reqHeaders = $transport->getLastRequestHeaders();

        $this->assertArrayHasKey('Host', $reqHeaders);
        $this->assertArrayHasKey('User-Agent', $reqHeaders);
    }

    public function testRetrieveTrace () {
        $expectedResponse = 'Retrieve Successful';
        $httpClient = $this->createMock($expectedResponse);
        $traceHeader = 'X-DGT-TRACETEST';
        $traceId = uniqid();
        $_SERVER['HTTP_' . strtoupper ($traceHeader)] = $traceId;

        $transport = new GuzzleHttpTransport($httpClient, $traceHeader);

        $transport->retrieve('/tonton', new RequestArguments());
        $reqHeaderValue = $transport->getLastRequestHeader($traceHeader);

        $this->assertEquals($traceId, $reqHeaderValue);
    }

    public function testSetHeaderTrace () {
        $expectedResponse = 'Retrieve Successful';
        $httpClient = $this->createMock($expectedResponse);
        $traceHeader = 'X-DGT-TRACETEST';
        $traceId = uniqid();
        $_SERVER['HTTP_' . strtoupper ($traceHeader)] = $traceId;

        $transport = new GuzzleHttpTransport($httpClient, $traceHeader);

        $res = $transport->getHeaderTraceName();

        $this->assertEquals($traceHeader, $res);
    }

    public function testRetrieveArguments () {
        $expectedResponse = 'Retrieve Successful';
        $httpClient = $this->createMock($expectedResponse);

        $arguments = new RequestArguments(3);

        $arguments[0] = new RequestArgument('arg1', 'value1');
        $arguments[1] = new RequestArgument('arg2', 'value2');
        $arguments[2] = new RequestArgument('arg3', 'value3');

        $transport = new GuzzleHttpTransport($httpClient);

        $transport->retrieve('/tonton', $arguments);

        $query = $transport->getLastRequestQueryString();
        
        $this->assertContains("arg1", $query);
        $this->assertContains("arg2", $query);
        $this->assertContains("arg3", $query);
        $this->assertContains("value1", $query);
        $this->assertContains("value2", $query);
        $this->assertContains("value3", $query);
    }

    public function testCreate () {
        $expectedResponse = 'Create Successful';
        $httpClient = $this->createMock($expectedResponse, 200, 'http://api.myapp.com');
        $body = "foo=bar";
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create ('/tonton', new RequestArguments(), $body);

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('api.myapp.com', $transport->getLastRequestHost());
        $this->assertEquals(80, $transport->getLastRequestPort());
        $this->assertEquals('post', strtolower ($transport->getLastRequestMethod()));
        $this->assertEquals('http://api.myapp.com/tonton', $transport->getLastRequestUrl());
        $this->assertEquals($body, $transport->getLastRequestBody());
    }

    public function testUpdate () {
        $expectedResponse = 'Update Successful';
        $httpClient = $this->createMock($expectedResponse, 200, 'http://api.myapp.com');
        $body = "foo=bar";
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update('/tonton', new RequestArguments(), $body);

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('api.myapp.com', $transport->getLastRequestHost());
        $this->assertEquals(80, $transport->getLastRequestPort());
        $this->assertEquals('put', strtolower ($transport->getLastRequestMethod()));
        $this->assertEquals('http://api.myapp.com/tonton', $transport->getLastRequestUrl());
        $this->assertEquals($body, $transport->getLastRequestBody());
    }

    public function testDelete () {
        $expectedResponse = 'Create Successful';
        $httpClient = $this->createMock($expectedResponse, 200, 'http://api.myapp.com');
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());

        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('api.myapp.com', $transport->getLastRequestHost());
        $this->assertEquals(80, $transport->getLastRequestPort());
        $this->assertEquals('delete', strtolower ($transport->getLastRequestMethod()));
        $this->assertEquals('http://api.myapp.com/tonton', $transport->getLastRequestUrl());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException
     */
    public function testCreateNotFound () {
        $expectedResponse = 'Create not found';
        $httpClient = $this->createMock($expectedResponse, 404);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException
     */
    public function testCreateForbidden () {
        $expectedResponse = 'Create forbidden';
        $httpClient = $this->createMock($expectedResponse, 403);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testCreateError5xx () {
        $expectedResponse = 'Create exception';
        $httpClient = $this->createMock($expectedResponse, 500);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testCreateConnectionTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new ConnectException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testCreateReadTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new RequestException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->create('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException
     */
    public function testUpdateNotFound () {
        $expectedResponse = 'Create not found';
        $httpClient = $this->createMock($expectedResponse, 404);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException
     */
    public function testUpdateForbidden () {
        $expectedResponse = 'Update forbidden';
        $httpClient = $this->createMock($expectedResponse, 403);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testUpdateError5xx () {
        $expectedResponse = 'Update exception';
        $httpClient = $this->createMock($expectedResponse, 500);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testUpdateConnectionTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new ConnectException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testUpdateReadTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new RequestException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->update ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException
     */
    public function testDeleteNotFound () {
        $expectedResponse = 'Create not found';
        $httpClient = $this->createMock($expectedResponse, 404);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException
     */
    public function testDeleteForbidden () {
        $expectedResponse = 'Delete forbidden';
        $httpClient = $this->createMock($expectedResponse, 403);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testDeleteError5xx () {
        $expectedResponse = 'Delete exception';
        $httpClient = $this->createMock($expectedResponse, 500);
        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testDeleteConnectionTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new ConnectException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());
    }

    /**
     * @expectedException \Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException
     */
    public function testDeleteReadTimeout () {
        $baseUrl = 'http://api.myapp.com';

        $httpClient = new Client([
            'base_url' => $baseUrl
        ]);
        $httpMock = new Mock();
        $httpMock->addException(
            new RequestException(
                'Time out',
                new Request('post', '/')
            )
        );
        $httpClient->getEmitter()->attach($httpMock);

        $transport = new GuzzleHttpTransport($httpClient);
        $response = $transport->delete ('/tonton', new RequestArguments());
    }
}