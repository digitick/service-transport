<?php

namespace Digitick\Bridge\ServiceTransport;


use Digitick\Bridge\ServiceTransport\Request\RequestArguments;

interface NetworkTransportInterface
{
    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @param string $message
     * @return mixed
     */
    public function create ($endpoint, RequestArguments $arguments, $message = '');

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @return mixed
     */
    public function retrieve ($endpoint, RequestArguments $arguments);

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @param string $message
     * @return mixed
     */
    public function update ($endpoint, RequestArguments $arguments, $message = '');

    /**
     * @param $endpoint
     * @param RequestArguments $arguments
     * @return mixed
     */
    public function delete ($endpoint, RequestArguments $arguments);

    /**
     * @return mixed
     */
    public function getBaseUrl ();

}