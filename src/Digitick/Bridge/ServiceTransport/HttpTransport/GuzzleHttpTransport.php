<?php


namespace Digitick\Bridge\ServiceTransport\HttpTransport;

use Digitick\Bridge\ServiceTransport\Request\RequestArguments;
use Digitick\Bridge\ServiceTransport\NetworkTransportInterface;
use Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException;
use Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException;
use Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Request;

class GuzzleHttpTransport implements NetworkTransportInterface
{
    const TRACE_HTTP_HEADER = 'X-DGT-TRACE';
    const CONNECTION_TIMEOUT_DEFAULT = 10;
    const READ_TIMEOUT_DEFAULT = 10;

    /**
     * @var ClientInterface
     */
    private $httpClient = null;

    /**
     * @var string
     */
    private $headerTraceName = '';

    /**
     * @var Request
     */
    private $lastRequest = null;

    public function __construct(ClientInterface $guzzleClient, $headerTraceName = self::TRACE_HTTP_HEADER)
    {
        $this->httpClient = $guzzleClient;
        $this->headerTraceName = $headerTraceName;
    }

    /**
     * @inheritDoc
     */
    public function create($endpoint, RequestArguments $arguments = null, $message = '')
    {
        try {
            $request = $this->httpClient->createRequest(
                'post',
                $endpoint,
                [
                    'body' => $message,
                    'query' => ($arguments != null ? $arguments->toArray() : []),
                    'headers' => $this->getTracingHeaders()
                ]
            );
            $this->lastRequest = $request;
            $response = $this->httpClient->send($request);

            return $response->getBody();
        } catch (TransferException $exc) {
            throw $this->ExceptionFactory($exc);
        }
    }

    /**
     * @inheritDoc
     */
    public function retrieve($endpoint, RequestArguments $arguments = null)
    {
        try {
            $request = $this->httpClient->createRequest(
                'get',
                $endpoint,
                [
                    'query' => ($arguments != null ? $arguments->toArray() : []),
                    'headers' => $this->getTracingHeaders()
                ]
            );
            $this->lastRequest = $request;
            $response = $this->httpClient->send ($request);
            return $response->getBody();
        } catch (TransferException $exc) {
            throw $this->ExceptionFactory($exc);
        }
    }

    /**
     * @inheritDoc
     */
    public function update($endpoint, RequestArguments $arguments = null, $message = '')
    {
        try {
            $request = $this->httpClient->createRequest(
                'put',
                $endpoint,
                [
                    'body' => $message,
                    'query' => ($arguments != null ? $arguments->toArray() : []),
                    'headers' => $this->getTracingHeaders()
                ]
            );
            $this->lastRequest = $request;
            $response = $this->httpClient->send($request);

            return $response->getBody();
        } catch (TransferException $exc) {
            throw $this->ExceptionFactory($exc);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete($endpoint, RequestArguments $arguments = null)
    {
        try {
            $request = $this->httpClient->createRequest(
                'delete',
                $endpoint,
                [
                    'query' => ($arguments != null ? $arguments->toArray() : []),
                    'headers' => $this->getTracingHeaders()
                ]
            );
            $this->lastRequest = $request;
            $response = $this->httpClient->send($request);

            return $response->getBody();
        } catch (TransferException $exc) {
            throw $this->ExceptionFactory($exc);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function getBaseUrl()
    {
        return $this->httpClient->getBaseUrl();
    }

    /**
     * @return string
     */
    public function getHeaderTraceName () {
        return $this->headerTraceName;
    }

    /**
     * @return string
     */
    public function getLastRequestUrl () {
        return $this->lastRequest->getUrl();
    }

    public function getLastRequestQueryString () {
        return $this->lastRequest->getQuery()->__toString();
    }

    /**
     * @return array
     */
    public function getLastRequestHeaders () {
        return $this->lastRequest->getHeaders();
    }

    /**
     * @param $headerName
     * @return array|mixed|\string[]
     */
    public function getLastRequestHeader ($headerName) {
        return $this->lastRequest->getHeader($headerName);
    }

    /**
     * @return string
     */
    public function getLastRequestHost () {
        return $this->lastRequest->getHost();
    }

    /**
     * @return int|null
     */
    public function getLastRequestPort () {
        return $this->lastRequest->getPort();
    }

    /**
     * @return string
     */
    public function getLastRequestMethod () {
        return $this->lastRequest->getMethod();
    }

    public function getLastRequestBody () {
        $body = '';
        if ($this->lastRequest->getBody() !== null && $this->lastRequest->getBody()->getSize() > 0) {
            if ($this->lastRequest->getBody()->isSeekable()) {
                $previousPosition = $this->lastRequest->getBody()->tell();
                $this->lastRequest->getBody()->seek(0);
            }

            $body = $this->lastRequest->getBody()->getContents();

            if ($this->lastRequest->getBody()->isSeekable()) {
                $this->lastRequest->getBody()->seek($previousPosition);
            }
        }

        return $body;
    }

    protected function getTracingHeaders () {
        $traceHeader = [];

        $serverKey = 'HTTP_' . strtoupper ($this->headerTraceName);
        if (isset ($_SERVER [$serverKey])) {
            $traceHeader [$this->headerTraceName] = $_SERVER [$serverKey];
        }
        return $traceHeader;
    }

    protected function ExceptionFactory (TransferException $exc) {
        if ($exc instanceof ClientException) {
            if ($exc->getCode() == NotFoundException::STATUS_CODE) {
                return new NotFoundException ($exc->getMessage(), NotFoundException::STATUS_CODE, $exc);
            } else if ($exc->getCode() == ForbiddenException::STATUS_CODE) {
                return new ForbiddenException($exc->getMessage(), $exc->getCode(), $exc);
            }
        } else {
            return new TransportUnavailableException  ($exc->getMessage(), $exc->getCode(), $exc);
        }
    }
}