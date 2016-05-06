<?php

namespace Digitick\Bridge\ServiceTransport;


use Digitick\Bridge\ServiceTransport\Exception\Network\NotFoundException as NetworkNotFoundException;
use Digitick\Bridge\ServiceTransport\Exception\Network\ForbiddenException as NetworkForbiddenException;
use Digitick\Bridge\ServiceTransport\Request\RequestArguments;
use Digitick\Bridge\ServiceTransport\Exception\Network\ApplicationException;
use Digitick\Bridge\ServiceTransport\Exception\Serialization\SerializationException;
use Digitick\Bridge\ServiceTransport\Exception\ServiceLogicException;
use Digitick\Bridge\ServiceTransport\Exception\ServiceUnavailableException;
use Digitick\Bridge\ServiceTransport\Exception\Network\TransportUnavailableException;
use Digitick\Bridge\ServiceTransport\Exception\UnexpectedResponseException;


class ServiceTransport implements ServiceTransportInterface
{
    /**
     * @var NetworkTransportInterface $transport
     */
    private $transport = null;

    /**
     * @var ServiceSerializerInterface|null
     */
    private $serializer = null;

    /**
     * @var CircuitBreakerInterface
     */
    private $circuitBreaker;

    /**
     * ServiceTransport constructor.
     * @param NetworkTransportInterface $transport
     * @param CircuitBreakerInterface $circuitBreaker
     * @param ServiceSerializerInterface $serializer
     */
    public function __construct(NetworkTransportInterface $transport, CircuitBreakerInterface $circuitBreaker = null, ServiceSerializerInterface $serializer = null)
    {
        $this->transport = $transport;
        $this->serializer = $serializer;
        $this->circuitBreaker = $circuitBreaker;
    }

    /**
     * @inheritdoc
     */
    public function create($endpoint, RequestArguments $arguments = null, $data = null)
    {
        return $this->callMethodWithBody('create', $endpoint, $arguments, $data);
    }

    /**
     * @inheritdoc
     */
    public function retrieve($endpoint, RequestArguments $arguments = null)
    {
        return $this->callMethodWithoutBody('retrieve', $endpoint, $arguments);
    }

    /**
     * @inheritdoc
     */
    public function update($endpoint, RequestArguments $arguments = null, $data = null)
    {
        return $this->callMethodWithBody('update', $endpoint, $arguments, $data);
    }

    /**
     * @inheritdoc
     */
    public function delete($endpoint, RequestArguments $arguments = null)
    {
        return $this->callMethodWithoutBody('delete', $endpoint, $arguments);
    }

    protected function callMethodWithoutBody ($method, $endpoint, RequestArguments $arguments = null) {
        $response = null;

        if ($this->circuitBreaker == null) {
            $response = $this->transport->$method($endpoint, $arguments);
            return $this->unserialize($response);
        }

        $cbKey = $this->getCircuitBreakerServiceName($endpoint);

        if ($this->circuitBreaker->isAvailable($cbKey)) {
            try {
                $response = $this->transport->$method($endpoint, $arguments);
                $response = $this->unserialize($response);
                $this->circuitBreaker->reportSuccess($cbKey);

            } catch (TransportUnavailableException $exc) {
                $this->circuitBreaker->reportFailure($cbKey);
                throw new ServiceUnavailableException ("Unreacheable service at endpoint $endpoint", 0, $exc);

            } catch (ApplicationException $exc) {
                throw new ServiceLogicException ("Service report an error at endpoint $endpoint", 1, $exc);

            } catch (SerializationException $exc) {
                throw new UnexpectedResponseException ("Bad response from endpoint $endpoint", 1, $exc);
            }
        } else {
            throw new ServiceUnavailableException ();
        }

        return $response;
    }

    protected function callMethodWithBody ($method, $endpoint, RequestArguments $arguments = null, $data = null) {
        $response = null;

        if ($this->circuitBreaker == null) {
            $response = $this->transport->update(
                $endpoint,
                $arguments,
                $this->serialize($data)
            );
            return $this->unserialize($response);
        }

        $cbKey = $this->getCircuitBreakerServiceName($endpoint);

        if ($this->circuitBreaker->isAvailable($cbKey)) {
            try {
                $response = $this->unserialize(
                    $this->transport->$method(
                        $endpoint,
                        $arguments,
                        $this->serialize($data)
                    )
                );
                $this->circuitBreaker->reportSuccess($cbKey);

            } catch (TransportUnavailableException $exc) {
                $this->circuitBreaker->reportFailure($cbKey);
                throw new ServiceUnavailableException ("Unreacheable service at endpoint $endpoint", 0, $exc);

            } catch (NetworkNotFoundException $exc) {
                throw new ServiceLogicException ("Service report an error at endpoint $endpoint", 1, $exc);

            } catch (NetworkForbiddenException $exc) {
                throw new ServiceLogicException ("Service report an error at endpoint $endpoint", 1, $exc);

            } catch (SerializationException $exc) {
                throw new UnexpectedResponseException ("Bad response from endpoint $endpoint", 1, $exc);
            }
        } else {
            throw new ServiceUnavailableException ();
        }

        return $response;
    }

    private function getCircuitBreakerServiceName ($endpoint) {
        return $this->transport->getBaseUrl() . $endpoint;
    }

    private function serialize ($data = null) {
        if ($data == null) {
            return null;
        }
        if ($this->serializer !== null) {
            return $this->serializer->serialize($data);
        }
        return $data;
    }

    private function unserialize ($data) {
        if ($this->serializer !== null) {
            return $this->serializer->unserialize($data);
        }
        return $data;
    }

}