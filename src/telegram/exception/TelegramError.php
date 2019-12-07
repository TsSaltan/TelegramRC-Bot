<?php


namespace telegram\exception;


use Throwable;

class TelegramError extends \Exception{
    function __construct(int $code, ?string $message = null){
        parent::__construct($message, $code);
    }
}