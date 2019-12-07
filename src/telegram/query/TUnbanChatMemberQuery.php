<?php


namespace telegram\query;


use telegram\TelegramBotApi;

class TUnbanChatMemberQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'unbanChatMember', false);
    }
    /**
     * @return TUnbanChatMemberQuery
     */
    public function chat_id($value){
        return $this->put(__FUNCTION__, $value);
    }
    /**
     * @return TUnbanChatMemberQuery
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