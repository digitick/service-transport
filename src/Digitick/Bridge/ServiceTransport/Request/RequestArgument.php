<?php


namespace Digitick\Bridge\ServiceTransport\Request;


class RequestArgument
{
    protected $argumentName;
    protected $argumentValue;

    /**
     * RequestArgument constructor.
     * @param $argumentName
     * @param $argumentValue
     */
    public function __construct($argumentName, $argumentValue)
    {
        $this->argumentName = $argumentName;
        $this->argumentValue = $argumentValue;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->argumentName;
    }

    /**
     * @param mixed $argumentName
     * @return RequestArgument
     */
    public function setName($argumentName)
    {
        $this->argumentName = $argumentName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->argumentValue;
    }

    /**
     * @param mixed $argumentValue
     * @return RequestArgument
     */
    public function setValue($argumentValue)
    {
        $this->argumentValue = $argumentValue;
        return $this;
    }

    

}