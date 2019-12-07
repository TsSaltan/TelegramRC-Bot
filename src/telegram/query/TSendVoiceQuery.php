<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendVoiceQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendVoice', true);
    }
    /**
     * @return TSendVoiceQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVoiceQuery
     */
    public function voice($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVoiceQuery
     */
    public function caption($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVoiceQuery
     */
    public function duration($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVoiceQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendVoiceQuery
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