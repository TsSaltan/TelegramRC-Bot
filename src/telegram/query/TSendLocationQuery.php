<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendLocationQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendLocation', false);
    }
    /**
     * @return TSendLocationQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendLocationQuery
     */
    public function latitude($value){
        return $this->put(__FUNCTION__, (float)$value);
    }
    /**
     * @return TSendLocationQuery
     */
    public function longitude($value){
        return $this->put(__FUNCTION__, (float)$value);
    }
    /**
     * @return TSendLocationQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TSendLocationQuery
     */
    public function reply_to_message_id($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TMessage
     */
    public function query(){
        return parent::query();
    }
}