<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendDocumentQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendDocument', true);
    }
    /**
     * @return TSendDocumentQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendDocumentQuery
     */
    public function document($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendDocumentQuery
     */
    public function caption($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendDocumentQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendDocumentQuery
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