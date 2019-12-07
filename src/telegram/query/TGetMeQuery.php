<?php


namespace telegram\query;


use telegram\object\TUser;
use telegram\TelegramBotApi;

class TGetMeQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'getMe', false);
    }

    /**
     * @return TUser
     */
    public function query(){
        return parent::query();
    }
}