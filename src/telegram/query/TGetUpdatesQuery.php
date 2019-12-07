<?php


namespace telegram\query;


use telegram\object\TUpdate;
use telegram\TelegramBotApi;

class TGetUpdatesQuery extends TBaseQuery{
    public function __construct(TelegramBotApi $api){
        parent::__construct($api, 'getUpdates', false);
    }
    /**
     * @return TGetUpdatesQuery
     */
    public function offset($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TGetUpdatesQuery
     */
    public function limit($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TGetUpdatesQuery
     */
    public function timeout($value){
        return $this->put(__FUNCTION__, (int)$value);
    }
    /**
     * @return TUpdate[]
     */
    public function query(){
        return parent::query();
    }
}