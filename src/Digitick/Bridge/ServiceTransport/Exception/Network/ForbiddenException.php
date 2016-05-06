<?php


namespace Digitick\Bridge\ServiceTransport\Exception\Network;


class ForbiddenException extends ApplicationException
{
    const STATUS_CODE = 403;
}