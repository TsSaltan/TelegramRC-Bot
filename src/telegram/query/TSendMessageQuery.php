<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendMessageQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendMessage', false);
    }
    /**
     * @return TSendMessageQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function text($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function parse_mode($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function disable_web_page_preview($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, (bool)$value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function reply_to_message_id($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TSendMessageQuery
     */
    public function reply_markup($value){
        return $this->put(__FUNCTION__, $value);
    }
    
    /**
     * @return TMessage
     */
    public function query(){
        return parent::query();
    }
}