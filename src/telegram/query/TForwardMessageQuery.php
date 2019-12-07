<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TForwardMessageQuery extends TBaseQuery
{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'forwardMessage', false);
    }
    /**
     * @return TForwardMessageQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TForwardMessageQuery
     */
    public function from_chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TForwardMessageQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TForwardMessageQuery
     */
    public function message_id($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TMessage
     */
    public function query(){
        return parent::query();
    }
}