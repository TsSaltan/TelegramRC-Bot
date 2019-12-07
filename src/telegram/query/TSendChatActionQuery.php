<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendChatActionQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendChatAction', false);
    }
    /**
     * @return TSendChatActionQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendChatActionQuery
     * typing
     * upload_photo
     * record_video, upload_video
     * record_audio, upload_audio
     * upload_document
     * find_location
     */
    public function action($value){
        return $this->put(__FUNCTION__, (string)$value);
    }
    /**
     * @return bool
     */
    public function query(){
        return parent::query();
    }
}