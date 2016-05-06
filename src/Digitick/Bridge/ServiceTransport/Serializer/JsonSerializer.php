<?php


namespace Digitick\Bridge\ServiceTransport\Serializer;


use Digitick\Bridge\ServiceTransport\Exception\Serialization\JsonSerializationException;
use Digitick\Bridge\ServiceTransport\ServiceSerializerInterface;

class JsonSerializer implements ServiceSerializerInterface
{
    /**
     * @inheritdoc
     */
    public function serialize($object)
    {
        $data = json_encode($object);
        if ($data === false) {
            throw JsonSerializationException::buildFromJsonError(json_last_error());
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function unserialize($object)
    {
        $data = json_decode($object, true);
        if ($data === NULL) {
            throw JsonSerializationException::buildFromJsonError(json_last_error());
        }
        return $data;
    }

}