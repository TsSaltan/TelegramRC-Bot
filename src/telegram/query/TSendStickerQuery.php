<?php


namespace telegram\query;


use telegram\object\TMessage;
use telegram\TelegramBotApi;

class TSendStickerQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'sendSticker', true);
    }
    /**
     * @return TSendStickerQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendStickerQuery
     */
    public function sticker($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendStickerQuery
     */
    public function disable_notification($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TSendStickerQuery
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