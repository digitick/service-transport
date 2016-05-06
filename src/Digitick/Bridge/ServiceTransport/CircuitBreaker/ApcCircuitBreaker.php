<?php


namespace Digitick\Bridge\ServiceTransport\CircuitBreaker;


use Digitick\Bridge\ServiceTransport\CircuitBreakerInterface;
use Ejsmont\CircuitBreaker\Factory;

class ApcCircuitBreaker implements CircuitBreakerInterface
{
    const MAX_FAILURES_DEFAULT = 20;
    const MAX_RETRY_TIMEOUT_DEFAULT = 5;

    /**
     * @var \Ejsmont\CircuitBreaker\CircuitBreakerInterface
     */
    private $circuitBreaker = null;

    private $maxFailures;
    private $retryTimeout;

    /**
     * CircuitBreaker constructor using APC as storage.
     * @param int $maxFailures How many times do we allow service to fail before considering it unavailable
     * @param int $retryTimeout How many seconds should we wait before attempting retry
     */
    public function __construct($maxFailures = self::MAX_FAILURES_DEFAULT,
                                $retryTimeout = self::MAX_RETRY_TIMEOUT_DEFAULT)
    {
        $this->maxFailures = $maxFailures;
        $this->retryTimeout = $retryTimeout;

        $factory = new Factory();
        $this->circuitBreaker = $factory->getSingleApcInstance($this->maxFailures, $this->retryTimeout);
    }

    /**
     * @inheritdoc
     */
    public function isAvailable($serviceName)
    {
        return $this->circuitBreaker->isAvailable($serviceName);
    }

    /**
     * @inheritdoc
     */
    public function reportSuccess($serviceName)
    {
        $this->circuitBreaker->reportSuccess($serviceName);
    }

    /**
     * @inheritdoc
     */
    public function reportFailure($serviceName)
    {
        $this->circuitBreaker->reportFailure($serviceName);
    }

    public function setServiceSettings ($serviceName, $maxFailures, $retryTimeout) {
        return $this->circuitBreaker->setServiceSettings ($serviceName, $maxFailures, $retryTimeout);
    }
}