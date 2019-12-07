<?php


namespace telegram\query;


use telegram\TelegramBotApi;

class TKickChatMemberQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'kickChatMember', false);
    }
    /**
     * @return TKickChatMemberQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TKickChatMemberQuery
     */
    public function user_id($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return bool
     */
    public function query(){
        return parent::query();
    }
}