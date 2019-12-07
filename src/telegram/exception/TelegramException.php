<?php


namespace telegram\exception;


use Throwable;

class TelegramException extends \Exception{
    public function __construct(string $message, ?Throwable $previous = null){
        parent::__construct($message, null, $previous);
    }
}