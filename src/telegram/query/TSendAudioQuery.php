<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendAudioQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendAudio', true);
    }
    /**
     * @return TSendAudioQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function audio($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function caption($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function title($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function performer($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function duration($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendAudioQuery
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