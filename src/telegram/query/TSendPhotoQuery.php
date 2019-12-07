<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendPhotoQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendPhoto', true);
    }
    /**
     * @return TSendPhotoQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendPhotoQuery
     */
    public function photo($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendPhotoQuery
     */
    public function caption($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendPhotoQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendPhotoQuery
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