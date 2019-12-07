<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TEditMessageTextQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'editMessageText', false);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function message_id($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function inline_message_id($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function text($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function parse_mode($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return TEditMessageTextQuery
     */
    public function disable_web_page_preview($value){
        return $this->put(__FUNCTION__, (bool)$value);
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