<?php
namespace Digitick\Bridge\ServiceTransport;

use Digitick\Bridge\ServiceTransport\ServiceResponse;
use Digitick\Bridge\ServiceTransport\Request\RequestArguments;

interface ServiceTransportInterface
{
    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @param $data
     * @return \Digitick\Bridge\ServiceTransport\ServiceResponse
     */
    public function create ($endpoint, RequestArguments $arguments, $data);

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @return ServiceResponse
     */
    public function retrieve ($endpoint, RequestArguments $arguments);

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @param $data
     * @return \Digitick\Bridge\ServiceTransport\ServiceResponse
     */
    public function update ($endpoint, RequestArguments $arguments, $data);

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @return ServiceResponse
     */
    public function delete ($endpoint, RequestArguments $arguments);
}