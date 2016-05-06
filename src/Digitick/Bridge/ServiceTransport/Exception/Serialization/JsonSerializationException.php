<?php


namespace Digitick\Bridge\ServiceTransport\Exception\Serialization;


class JsonSerializationException extends SerializationException
{
    public static function buildFromJsonError ($jsonError) {
        $errorMsg = 'Unknown error';

        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                $errorMsg = 'The maximum stack depth has been exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $errorMsg = 'Invalid or malformed JSON';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $errorMsg = 'Control character error, possibly incorrectly encoded';
                break;
            case JSON_ERROR_SYNTAX:
                $errorMsg = 'Syntax error';
                break;
            case JSON_ERROR_UTF8:
                $errorMsg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
        }
        return new JsonSerializationException($errorMsg, json_last_error());
    }
}