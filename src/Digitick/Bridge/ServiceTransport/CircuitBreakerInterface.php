<?php

namespace Digitick\Bridge\ServiceTransport;


interface CircuitBreakerInterface
{
    /**
     * Check if a service as been reported available
     * @param string $serviceName Name of the service to test availibility
     * @return bool True if the service is available, false otherwise
     */
    public function isAvailable ($serviceName);

    /**
     * Report that a service is available
     *
     * @param string $serviceName Name of the service to report availibility
     */
    public function reportSuccess ($serviceName);

    /**
     * Report that a service is unavailable
     *
     * @param string $serviceName Name of the service to report availibility
     */
    public function reportFailure ($serviceName);
}