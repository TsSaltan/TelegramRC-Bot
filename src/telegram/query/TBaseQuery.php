<?php


namespace telegram\query;


use telegram\TelegramBotApi;

abstract class TBaseQuery{
    /**
     * @var TelegramBotApi
     */
    private $api;
    private $method;
    private $multipart;
    private $data = [];

    protected function __construct(TelegramBotApi $api, string $method, bool $multipart = false){
        $this->api = $api;
        $this->method = $method;
        $this->multipart = $multipart;
    }
    /**
     * @return TelegramBotApi
     */
    protected final function getApi() : TelegramBotApi{
        return $this->api;
    }
    protected function isMultipart() : bool {
        return $this->multipart;
    }
    protected function getMethod() : string {
        return $this->method;
    }

    protected final function put(string $key, $value){
        $this->data[$key] = $value;
        return $this;
    }

    public function query(){
        return $this->api->query($this->method, $this->data, $this->multipart);
    }
}