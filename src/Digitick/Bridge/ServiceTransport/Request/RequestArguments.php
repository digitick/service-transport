<?php

namespace Digitick\Bridge\ServiceTransport\Request;

class RequestArguments extends \SplFixedArray
{
    /**
     * @inheritDoc
     */
    public function offsetSet($index, $newval)
    {
        if (!$newval instanceof RequestArgument) {
            throw new \InvalidArgumentException ();
        }
        parent::offsetSet($index, $newval);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray($data, $save_indexes = true)
    {
        $dataLength = count($data);

        if ($dataLength == 0) {
            return new RequestArguments();
        }

        if ($save_indexes) {
            $maxIndexKey = max (array_keys($data));
            if ($maxIndexKey >= $dataLength) {
                $save_indexes = false;
            }
        }

        $instance = new RequestArguments($dataLength);

        $index = 0;
        foreach ($data as $dataIndex => $value) {
            if (!$value instanceof RequestArgument) {
                throw new \InvalidArgumentException ();
            }
            $arrayIndex = $index;
            if ($save_indexes == true) {
                $arrayIndex = $dataIndex;
            }
            $instance [$arrayIndex] = $value;
            $index++;
        }

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $arr = [];

        foreach ($this as $item) {
            $arr [$item->getName()] = $item->getValue();
        }

        return $arr;
    }


}