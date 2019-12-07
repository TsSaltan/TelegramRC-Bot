<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendVideoQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendVideo', true);
    }
    /**
     * @return TSendVideoQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVideoQuery
     */
    public function duration($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVideoQuery
     */
    public function video($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVideoQuery
     */
    public function caption($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVideoQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVideoQuery
     */
    public function reply_to_message_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /*
    public function reply_markup($value){
    return $this->put(__FUNCTION__, $value);
    }
    */
    /**
     * @return TMessage
     */
    public function query(){
        return parent::query();
    }
}