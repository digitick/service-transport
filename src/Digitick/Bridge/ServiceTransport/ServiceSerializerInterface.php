<?php


namespace Digitick\Bridge\ServiceTransport;


interface ServiceSerializerInterface
{
    /**
     * Serialize an object
     * @param $object
     * @return mixed
     */
    public function serialize ($object);

    /**
     * Unserialize an object
     * @param $object
     * @return mixed
     */
    public function unserialize ($object);
}