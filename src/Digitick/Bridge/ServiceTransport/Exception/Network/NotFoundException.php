<?php


namespace Digitick\Bridge\ServiceTransport\Exception\Network;


class NotFoundException extends ApplicationException
{
    const STATUS_CODE = 404;
}