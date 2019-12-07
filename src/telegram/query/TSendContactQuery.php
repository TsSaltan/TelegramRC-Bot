<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendContactQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendContact', false);
    }
    /**
     * @return TSendContactQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendContactQuery
     */
    public function phone_number($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendContactQuery
     */
    public function first_name($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendContactQuery
     */
    public function last_name($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendContactQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TSendContactQuery
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